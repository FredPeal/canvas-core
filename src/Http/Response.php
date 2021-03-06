<?php

declare(strict_types=1);

namespace Canvas\Http;

use Phalcon\Http\Response as PhResponse;
use Phalcon\Mvc\Model\MessageInterface as ModelMessage;
use Phalcon\Validation\Message\Group as ValidationMessage;
use Canvas\Exception\ServerErrorHttpException;
use Canvas\Constants\Flags;
use Canvas\Http\Exception\InternalServerErrorException;
use Phalcon\Di;
use Throwable;

class Response extends PhResponse
{
    const OK = 200;
    const CREATED = 201;
    const ACCEPTED = 202;
    const MOVED_PERMANENTLY = 301;
    const FOUND = 302;
    const TEMPORARY_REDIRECT = 307;
    const PERMANENTLY_REDIRECT = 308;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const NOT_ACCEPTABLE = 406;
    const INTERNAL_SERVER_ERROR = 500;
    const NOT_IMPLEMENTED = 501;
    const BAD_GATEWAY = 502;
    const UNPROCESSABLE_ENTITY = 422;

    private $codes = [
        200 => 'OK',
        301 => 'Moved Permanently',
        302 => 'Found',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        422 => 'Unprocessable Entity',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
    ];

    /**
     * Returns the http code description or if not found the code itself.
     * @param int $code
     *
     * @return int|string
     */
    public function getHttpCodeDescription(int $code)
    {
        if (true === isset($this->codes[$code])) {
            return sprintf('%d (%s)', $code, $this->codes[$code]);
        }

        return $code;
    }

    /**
     * Send the response back.
     *
     * @return PhResponse
     */
    public function send(): PhResponse
    {
        $content = $this->getContent();
        $data = $content;
        $eTag = sha1($content);

        /**
         * At the moment we are only using this format for error msg.
         * @todo change in the future to implemente other formats
         */
        if ($this->getStatusCode() != 200) {
            $timestamp = date('c');
            $hash = sha1($timestamp . $content);

            /** @var array $content */
            $content = json_decode($this->getContent(), true);

            $jsonapi = [
                'jsonapi' => [
                    'version' => '1.0',
                ],
            ];
            $meta = [
                'meta' => [
                    'timestamp' => $timestamp,
                    'hash' => $hash,
                ]
            ];

            /**
             * Join the array again.
             */
            $data = $jsonapi + $content + $meta;
            $this->setJsonContent($data);
        }

        $this->setHeader('E-Tag', $eTag);

        return parent::send();
    }

    /**
     * Sets the payload code as Error.
     *
     * @param string $detail
     *
     * @return Response
     */
    public function setPayloadError(string $detail = ''): Response
    {
        $this->setJsonContent([
            'errors' => [
                'message' => $detail,
                'type' => $this->codes[404]
            ]
        ]);

        return $this;
    }

    /**
     * Traverses the errors collection and sets the errors in the payload.
     *
     * @param ModelMessage[]|ValidationMessage $errors
     *
     * @return Response
     */
    public function setPayloadErrors($errors): Response
    {
        $data = [];
        foreach ($errors as $error) {
            $data[] = $error->getMessage();
        }

        $this->setJsonContent(['errors' => $data]);

        return $this;
    }

    /**
     * Sets the payload code as Success.
     *
     * @param null|string|array $content The content
     *
     * @return Response
     */
    public function setPayloadSuccess($content = []): Response
    {
        $data = (true === is_array($content)) ? $content : ['data' => $content];
        $data = (true === isset($data['data'])) ? $data : ['data' => $data];

        $this->setJsonContent($data);

        return $this;
    }

    /**
     * Handle the exception we throw from our api.
     *
     * @param Throwable $e
     * @return Response
     */
    public function handleException(Throwable $e): Response
    {
        $request = new Request();
        $identifier = $request->getServerAddress();
        $config = Di::getDefault()->getConfig();

        $httpCode = (method_exists($e, 'getHttpCode')) ? $e->getHttpCode() : 404;
        $httpMessage = (method_exists($e, 'getHttpMessage')) ? $e->getHttpMessage() : 'Not Found';
        $data = (method_exists($e, 'getData')) ? $e->getData() : [];

        $this->setHeader('Access-Control-Allow-Origin', '*'); //@todo check why this fails on nginx
        $this->setStatusCode($httpCode, $httpMessage);
        $this->setContentType('application/json');
        $this->setJsonContent([
            'errors' => [
                'type' => $httpMessage,
                'identifier' => $identifier,
                'message' => $e->getMessage(),
                'trace' => strtolower($config->app->env) != Flags::PRODUCTION ? $e->getTraceAsString() : null,
                'data' => $data,
            ],
        ]);

        //only log when server error production is seerver error or dev
        if ($e instanceof ServerErrorHttpException || $e instanceof InternalServerErrorException || strtolower($config->app->env) != Flags::PRODUCTION) {
            Di::getDefault()->getLog()->error($e->getMessage(), [$e->getTraceAsString()]);
        }

        return $this;
    }
}

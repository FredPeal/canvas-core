<?php

declare(strict_types=1);

namespace Canvas\Api\Controllers;

use Canvas\Models\EmailTemplates;
use Canvas\Models\Users;
use Canvas\Http\Exception\NotFoundException;
use Phalcon\Security\Random;
use Phalcon\Http\Response;

/**
 * Class LanguagesController.
 *
 * @package Canvas\Api\Controllers
 *
 * @property Users $userData
 * @property Request $request
 * @property Config $config
 * @property \Baka\Mail\Message $mail
 * @property Apps $app
 *
 */
class EmailTemplatesController extends BaseController
{
    /*
     * fields we accept to create
     *
     * @var array
     */
    protected $createFields = [
        'users_id',
        'companies_id',
        'apps_id',
        'name',
        'template'
    ];

    /*
     * fields we accept to create
     *
     * @var array
     */
    protected $updateFields = [
        'users_id',
        'companies_id',
        'apps_id',
        'name',
        'template'
    ];

    /**
     * set objects.
     *
     * @return void
     */
    public function onConstruct()
    {
        $this->model = new EmailTemplates();
        $this->additionalSearchFields = [
            ['is_deleted', ':', '0'],
            ['companies_id', ':', '0|' . $this->userData->currentCompanyId()],
        ];
    }

    /**
     * Add a new by copying a specific email template based on.
     *
     * @method POST
     * @url /v1/data
     * @param integer $id
     * @return \Phalcon\Http\Response
     */
    public function copy(int $id): Response
    {
        $request = $this->request->getPostData();

        //Find email template based on the basic parameters
        $existingEmailTemplate = $this->model::findFirst([
            'conditions' => 'id = ?0 and companies_id in (?1,?2) and apps_id in (?3,?4) and is_deleted = 0',
            'bind' => [$id, $this->userData->currentCompanyId(), 0, $this->app->getId(), 0]
        ]);

        if (!is_object($existingEmailTemplate)) {
            throw new NotFoundException('Email Template not found');
        }

        $random = new Random();
        $randomInstance = $random->base58();

        $request['users_id'] = $existingEmailTemplate->users_id;
        $request['companies_id'] = $this->userData->currentCompanyId();
        $request['apps_id'] = $this->app->getId();
        $request['name'] = $existingEmailTemplate->name . '-' . $randomInstance;
        $request['template'] = $existingEmailTemplate->template;

        $this->model->saveOrFail($request, $this->createFields);

        return $this->response($this->model->toArray());
    }

    /**
     * Send test email to specific recipient.
     * @param string $email
     * @return Response
     */
    public function sendTestEmail(): Response
    {
        $request = $this->request->getPost();

        if (empty($request)) {
            $request = $this->request->getJsonRawBody(true);
        }

        $emailRecipients = explode(',', $request['emails']);

        foreach ($emailRecipients as $emailRecipient) {
            $userExists = Users::findFirst([
                'conditions' => 'email = ?0 and is_deleted = 0',
                'bind' => [$emailRecipient]
            ]);

            if (!is_object($userExists)) {
                throw new NotFoundException('Email recipient not found');
            }

            $subject = _('Test Email Template');
            $this->mail
                ->to((string)$userExists->email)
                ->subject($subject)
                ->content($request['template'])
                ->sendNow();
        }

        return $this->response('Test email sent');
    }
}

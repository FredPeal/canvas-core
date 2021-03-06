<?php

declare(strict_types=1);

namespace Canvas\Api\Controllers;

use Canvas\Models\Apps;
use Phalcon\Http\Response;

/**
 * Class LanguagesController.
 *
 * @package Canvas\Api\Controllers
 *
 */
class AppsController extends BaseController
{
    /*
     * fields we accept to create
     *
     * @var array
     */
    protected $createFields = [];

    /*
     * fields we accept to create
     *
     * @var array
     */
    protected $updateFields = [];

    /**
     * set objects.
     *
     * @return void
     */
    public function onConstruct()
    {
        $this->model = new Apps();
        $this->additionalSearchFields = [
            ['is_deleted', ':', '0'],
            ['id', ':', implode('|', $this->userData->getAssociatedApps())],
        ];
    }

    /**
     * get item.
     *
     * @param mixed $id
     *
     * @method GET
     * @url /v1/data/{id}
     *
     * @return \Phalcon\Http\Response
     */
    public function getById($id = null): Response
    {
        //find the info
        $record = $this->model->findFirstOrFail([
            'id = ?0 AND is_deleted = 0 AND id in (' . implode(',', $this->userData->getAssociatedApps()) . ')',
            'bind' => [$id],
        ]);

        //get the results and append its relationships
        $result = $this->appendRelationshipsToResult($this->request, $record);

        return $this->response($this->processOutput($result));
    }

    /**
     * Delete a Record.
     *
     * @throws Exception
     * @return Response
     */
    public function delete($id): Response
    {
        return $this->response('Cant delete app at the moment');
    }
}

<?php

declare(strict_types=1);

namespace Canvas\Traits;

use Phalcon\Http\Response;
use Phalcon\Validation;
use Phalcon\Validation\Validator\File as FileValidator;
use Canvas\Exception\UnprocessableEntityHttpException;
use Canvas\Models\FileSystem;
use Canvas\Filesystem\Helper;
use Baka\Http\QueryParser;
use Canvas\Models\FileSystemSettings;
use Canvas\Models\SystemModules;

/**
 * Trait ResponseTrait.
 *
 * @package Canvas\Traits
 *
 * @property Users $user
 * @property AppsPlans $appPlan
 * @property CompanyBranches $branches
 * @property Companies $company
 * @property UserCompanyApps $app
 * @property \Phalcon\Di $di
 *
 */
trait FileManagementTrait
{
    /**
     * Get item.
     *
     * @method GET
     * url /v1/filesystem/{id}
     *
     * @param mixed $id
     *
     * @return \Phalcon\Http\Response
     * @throws Exception
     */
    public function getById($id) : Response
    {
        $records = FileSystem::getById($id);

        //get relationship
        if ($this->request->hasQuery('relationships')) {
            $relationships = $this->request->getQuery('relationships', 'string');
            $records = QueryParser::parseRelationShips($relationships, $records);
        }

        if (!$records) {
            throw new UnprocessableEntityHttpException('Records not found');
        }

        return $this->response($records);
    }

    /**
     * Add a new item.
     *
     * @method POST
     * url /v1/filesystem
     *
     * @return \Phalcon\Http\Response
     * @throws Exception
     */
    public function create() : Response
    {
        if (!$this->request->hasFiles()) {
            //@todo handle base64 images
        }

        return $this->response($this->processFiles());
    }

    /**
     * Handle upload files from Uppy library.
     *
     * @return Response
     */
    public function createUppy(): Response
    {
        $request = $this->request->getPost();

        if (empty($request)) {
            $request = $this->request->getJsonRawBody(true);
        }

        return $this->response($request);
    }

    /**
     * Update an item.
     *
     * @method PUT
     * url /v1/filesystem/{id}
     *
     * @param mixed $id
     *
     * @return \Phalcon\Http\Response
     * @throws Exception
     */
    public function edit($id) : Response
    {
        $file = FileSystem::getById($id);

        $request = $this->request->getPut();

        if (empty($request)) {
            $request = $this->request->getJsonRawBody(true);
        }

        $systemModule = isset($request['system_modules_id']) ? $request['system_modules_id'] : $file->system_modules_id;
        $entityId = $request['entity_id'] ?? 0;

        $file->system_modules_id = $systemModule;
        $file->entity_id = $entityId;

        if (!$file->update()) {
            throw new UnprocessableEntityHttpException((string)current($file->getMessages()));
        }

        return $this->response($file);
    }

    /**
     * Delete a file atribute.
     *
     * @param $id
     * @param string $name
     * @return void
     */
    public function deleteAttributes($id, string $name): Response
    {
        $records = FileSystem::getById($id);

        if (!$records) {
            throw new UnprocessableEntityHttpException('Records not found');
        }

        $recordAttributes = FileSystemSettings::findFirst([
            'conditions' => 'filesystem_id = ?0 and name = ?1',
            'bind' => [$records->getId(), $name]
        ]);

        if (!is_object($recordAttributes)) {
            throw new UnprocessableEntityHttpException('File attribute not found');
        }

        //true true delete
        $recordAttributes->delete();

        return $this->response(['Delete Successfully']);
    }

    /**
     * Set the validation for the files.
     *
     * @return Validation
     */
    protected function validation(): Validation
    {
        $validator = new Validation();

        /**
         * @todo add validation for other file types, but we need to
         * look for a scalable way
         */
        $uploadConfig = [
            'maxSize' => '100M',
            'messageSize' => ':field exceeds the max filesize (:max)',
            'allowedTypes' => [
                'image/jpeg',
                'image/png',
                'audio/mpeg',
                'audio/mp3',
                'audio/mpeg',
                'application/pdf',
                'audio/mpeg3',
                'audio/x-mpeg-3',
                'application/x-zip-compressed',
                'application/octet-stream',
            ],
            'messageType' => 'Allowed file types are :types',
        ];

        $validator->add(
            'file',
            new FileValidator($uploadConfig)
        );

        return $validator;
    }

    /**
     * Upload the document and save them to the filesystem.
     *
     * @return array
     */
    protected function processFiles(): array
    {
        //@todo validate entity id
        $systemModule = $this->request->getPost('system_modules_id', 'int', '0');
        $entityId = $this->request->getPost('entity_id', 'int', '0');
        $allFields = $this->request->getPost();

        $validator = $this->validation();

        $files = [];
        foreach ($this->request->getUploadedFiles() as $file) {
            //validate this current file
            $errors = $validator->validate(['file' => [
                'name' => $file->getName(),
                'type' => $file->getType(),
                'tmp_name' => $file->getTempName(),
                'error' => $file->getError(),
                'size' => $file->getSize(),
            ]]);

            if (!defined('API_TESTS')) {
                if (count($errors)) {
                    foreach ($errors as $error) {
                        throw new UnprocessableEntityHttpException((string)$error);
                    }
                }
            }

            //get the filesystem config
            $appSettingFileConfig = $this->di->get('app')->get('filesystem');
            $fileSystemConfig = $this->config->filesystem->{$appSettingFileConfig};

            //create local filesystem , for temp files
            $this->di->get('filesystem', ['local'])->createDir($this->config->filesystem->local->path);

            //get the tem file
            $filePath = Helper::generateUniqueName($file, $this->config->filesystem->local->path);
            $compleFilePath = $fileSystemConfig->path . $filePath;
            $uploadFileNameWithPath = $appSettingFileConfig == 'local' ? $filePath : $compleFilePath;

            /**
             * upload file base on temp.
             * @todo change this to determine type of file and recreate it if its a image
             */
            $this->di->get('filesystem')->writeStream($uploadFileNameWithPath, fopen($file->getTempName(), 'r'));

            $fileSystem = new FileSystem();
            $fileSystem->name = $file->getName();
            $fileSystem->system_modules_id = $systemModule;
            $fileSystem->entity_id = $entityId;
            $fileSystem->companies_id = $this->userData->currentCompanyId();
            $fileSystem->apps_id = $this->app->getId();
            $fileSystem->users_id = $this->userData->getId();
            $fileSystem->path = $compleFilePath;
            $fileSystem->url = $fileSystemConfig->cdn . DIRECTORY_SEPARATOR . $uploadFileNameWithPath;
            $fileSystem->file_type = $file->getExtension();
            $fileSystem->size = $file->getSize();

            if (!$fileSystem->save()) {
                throw new UnprocessableEntityHttpException((string)current($fileSystem->getMessages()));
            }

            //add settings
            foreach ($allFields as $key => $settings) {
                $fileSystem->set($key, $settings);
            }

            $files[] = $fileSystem;
        }

        return $files;
    }
}

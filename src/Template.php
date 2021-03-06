<?php

declare(strict_types=1);

namespace Canvas;

use Canvas\Models\EmailTemplates;
use Phalcon\Di;
use Phalcon\Di\Injectable;

/**
 * Class Validation.
 *
 * @package Canvas
 */
class Template
{
    /**
     * Given the email tempalte name and its params
     *  - create the files
     *  - render it with the variables
     *  - return the content string for use to use anywhere.
     *
     * @param string $name
     * @param array $params
     * @return string
     */
    public static function generate(string $name, array $params): string
    {
        $di = Di::getDefault();
        $view = $di->getView();
        $filesystem = $di->get('filesystem', ['local']);

        //get the teamplate
        $template = EmailTemplates::getByName($name);
        $file = $template->name . '.volt';
        
        //write file
        $filesystem->put('/view/'.$file, $template->template);

        //rendre and return content
        return $view->render($template->name, $params);
    }
}

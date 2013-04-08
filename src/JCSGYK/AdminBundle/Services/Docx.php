<?php

namespace JCSGYK\AdminBundle\Services;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Service for Generatinc Docx files from templates
 */
class Docx
{
    private $template_dir;

    private $tbs;

    public function __construct($tbs)
    {
        $this->template_dir = __DIR__ . '/../Resources/templates/';
        $this->tbs = $tbs;
    }

    public function show($template, $fields, $file)
    {
        $this->tbs->LoadTemplate($this->template_dir . $template, false);

        foreach ($fields as $base => $merge) {
            $this->tbs->MergeField($base, $merge);
        }

        $this->tbs->Show(OPENTBS_DOWNLOAD, $file);
    }
}
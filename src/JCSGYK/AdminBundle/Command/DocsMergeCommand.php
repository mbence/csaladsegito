<?php

namespace JCSGYK\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Session\Session;

use JCSGYK\AdminBundle\Entity\Template;
use JCSGYK\AdminBundle\Entity\DocTemplate;

class DocsMergeCommand extends ContainerAwareCommand
{
    private $queryLimit = 1000;

    protected function configure()
    {
        $this
            ->setName('jcs:docs_merge')
            ->setDescription('Merge Documents data from files to db')
            ->addArgument('company', InputArgument::REQUIRED, 'Company ID')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $company_id = $input->getArgument('company');

        // set the companyid for the datastore
        $session = $this->getContainer()->get('session');
        $session->set('company_id', $company_id);

        $em = $this->getContainer()->get('doctrine')->getManager();

        $n = 0;
        $docs = $this->getDocs($company_id);
        foreach ($docs as $doc) {

            // create a doctemplate
            $new_doc = (new DocTemplate())
                ->setCompanyId($doc->getCompanyId())
                ->setName($doc->getName())
                ->setIsActive($doc->getIsActive())
                ->setDocType(DocTemplate::PROBLEM)
                ->setClientType($this->getClientType())
            ;
            $docpath = $doc->getAbsolutePath();
            if (file_exists($docpath)) {
                $new_doc
                    ->setOriginalName($doc->getOriginalName())
                    ->setFile($this->getFile($doc))
                    ->setMimeType($this->getMimeType($doc)) // old mimetypes are wrong
                ;
            }
            $em->persist($new_doc);

            $n ++;
        }
        $output->write(date('H:i:s: ') . $n . ' Doc merged', true);
        $em->flush();
    }

    private function getMimeType(Template $doc)
    {
        $finfo = new \finfo(FILEINFO_MIME);

        return $finfo->file($doc->getAbsolutePath());
    }

    private function getFile(Template $doc)
    {
        $file = '';
        $docpath = $doc->getAbsolutePath();
        if (file_exists($docpath)) {
            $file = file_get_contents($docpath);
        }

        return $file;
    }

    private function getClientType()
    {
        $ds = $this->getContainer()->get('jcs.ds');

        $client_types = $ds->getClientTypeNames(true);
        reset($client_types);

        return key($client_types);
    }

    /**
     * read the old docs
     * @param int $company_id
     * @return Template[]
     */
    private function getDocs($company_id)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();

        // get all the events, that have no history records
        return $em->createQuery("SELECT t FROM JCSGYKAdminBundle:Template t WHERE t.companyId=:company_id")
            ->setParameter('company_id', $company_id)
            ->getResult();
    }
}
<?php

$spec = Pearfarm_PackageSpec::create(array(Pearfarm_PackageSpec::OPT_BASEDIR => dirname(__FILE__)))
             ->setName('elibriWatermarkingPHP')
             ->setChannel('elibri.com.pl/system/pear')
             ->setSummary('Client for elibri watermarking and transactional system')
             ->setDescription('Client for elibri watermarking and transactional system')
             ->setReleaseVersion('0.2.4')
             ->setReleaseStability('beta')
             ->setApiVersion('1.0.0')
             ->setApiStability('beta')
             ->setLicense(Pearfarm_PackageSpec::LICENSE_MIT)
             ->setNotes('poprawione rozróżnianie numeru isbn od record_reference - w metodzie watermark')
             ->addMaintainer('lead', 'Tomasz Meka', '', 'tomek@elibri.com.pl')
             ->addFilesRegex("/elibriWatermarkingPHP\/.*php$/", $role= "php")
             ->addFilesRegex("/tests\//", $role= "test")
             ->addFilesRegex("/examples\/.*php$/", $role= "php")
             ->addFilesSimple("elibriWatermarkingPHP.php", $role= "php", array('baseinstalldir' => "/"))
             ->addFilesRegex("/html\//", $role= "doc")
             //->addGitFiles()
             //->addExecutable('elibriWatermarkingPHP')
             ;

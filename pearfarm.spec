<?php

$spec = Pearfarm_PackageSpec::create(array(Pearfarm_PackageSpec::OPT_BASEDIR => dirname(__FILE__)))
             ->setName('elibriWatermarkingPHP')
             ->setChannel('elibri.com.pl/system/pear')
             ->setSummary('Client for elibri watermarking and transactional system')
             ->setDescription('')
             ->setReleaseVersion('0.1.0')
             ->setReleaseStability('beta')
             ->setApiVersion('1.0.0')
             ->setApiStability('beta')
             ->setLicense(Pearfarm_PackageSpec::LICENSE_MIT)
             ->setNotes('Initial release.')
             ->addMaintainer('lead', 'Tomasz Meka', '', 'tomek@elibri.com.pl')
             //->addGitFiles()
             //->addExecutable('elibriWatermarkingPHP')
             ;

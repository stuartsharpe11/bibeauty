<?php
// src/AppBundle/Command/CreateAvailabilitiesCommand.php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CleanupAvailabilitiesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('bibeauty:availabilities:cleanup')
            ->setDescription('Cleanup availabilities that are no longer necessary and make new ones')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        if ($this->getContainer()->has('profiler')) {
            $this->getContainer()->get('profiler')->disable();
        }
        $availabilitySetId = $input->getArgument('id');

        $logger = $this->getContainer()->get('logger');
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        try {
            // $this->logger->addInfo('Start executing');


        } catch(\Exception $e) {
            $logger->addError($e->getMessage());
            return $e->getCode();
        }

        return 0;

    }
}

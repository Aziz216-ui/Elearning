<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class TestEmailCommand extends Command
{
    protected static $defaultName = 'app:test-email';
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        parent::__construct();
        $this->mailer = $mailer;
    }

    protected function configure()
    {
        $this
            ->setDescription('Envoie un email de test')
            ->addArgument('email', InputArgument::REQUIRED, 'Email du destinataire');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('melkimohamedaziz1@gmail.com')
            ->to($input->getArgument('email'))
            ->subject('Test d\'envoi d\'email')
            ->text('Ceci est un email de test.');

        try {
            $this->mailer->send($email);
            $output->writeln('Email envoyé avec succès à ' . $input->getArgument('email'));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Erreur lors de l\'envoi : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}

<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-user', description: 'Create a user with given email, password and roles')]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email of the user')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Plain password')
            ->addOption('roles', null, InputOption::VALUE_REQUIRED, 'Comma separated roles (e.g. ROLE_USER,ROLE_ADMIN)')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'First name', 'Admin')
            ->addOption('lastname', null, InputOption::VALUE_OPTIONAL, 'Last name', 'User')
            ->addOption('sexe', null, InputOption::VALUE_OPTIONAL, 'Sexe (M/F)', 'M');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getOption('email');
        $password = $input->getOption('password');
        $rolesOption = $input->getOption('roles');

        if (! $email) {
            $email = $io->ask('Email');
        }

        if (! $password) {
            $password = $io->askHidden('Password (will not be shown)');
        }

        if (! $rolesOption) {
            $rolesOption = $io->ask('Roles (comma separated)', 'ROLE_USER,ROLE_ADMIN');
        }

        $name = $input->getOption('name') ?: $io->ask('First name', 'Admin');
        $lastname = $input->getOption('lastname') ?: $io->ask('Last name', 'User');
        $sexe = $input->getOption('sexe') ?: $io->ask('Sexe (M/F)', 'M');

        $roles = array_filter(array_map('trim', explode(',', $rolesOption)));

        // Basic validation
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Email invalide.');
            return Command::FAILURE;
        }

        if (strlen((string) $password) < 6) {
            $io->error('Le mot de passe doit contenir au moins 6 caractères.');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setLastname($lastname);
        $user->setSexe($sexe);
        $user->setRoles($roles);

        // Hash password
        $hashed = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashed);

        $this->em->persist($user);
        $this->em->flush();

        $io->success(sprintf('User %s created with roles: %s', $email, implode(', ', $roles)));

        return Command::SUCCESS;
    }
}

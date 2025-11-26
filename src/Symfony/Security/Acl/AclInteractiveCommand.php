<?php

namespace Cosmologist\Gears\Symfony\Security\Acl;

use Cosmologist\Gears\StringType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Override;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * Command for interactively configuring ACLs
 *
 * A simple yet convenient alternative to the standard `acl:set` command from AclBundle
 * — no need to remember command semantics, user and object classes, worry about escaping, etc.
 * every time you need to add access rights.
 * The command allows you to interactively select a user identity and object identity from existing ACLs.
 * If you want to create an ACL from a new user/object identity, you should pass the appropriate arguments to the command.
 *
 * Enable the command:
 * ```
 * # config/services.yaml
 * services:
 *   _defaults:
 *     autowire: true
 *     autoconfigure: true
 *
 *   Cosmologist\Gears\Symfony\Security\Acl\AclInteractiveCommand:
 *     bind:
 *       $aclConnection: '@doctrine.dbal.security_connection'
 *       $aclProvider: '@security.acl.provider'
 *       $aclTables:
 *         table_classes: '%security.acl.dbal.class_table_name%'
 *         table_sid: '%security.acl.dbal.sid_table_name%'
 * ```
 */
#[AsCommand(name: 'acl:interactive', description: 'Interactively configure ACLs and ACEs based on existing entries')]
class AclInteractiveCommand extends Command
{
    public function __construct(private readonly Connection           $aclConnection,
                                private readonly AclProviderInterface $aclProvider,
                                private readonly array                $aclTables)
    {
        parent::__construct();
    }

    #[Override]
    protected function configure()
    {
        $this
            ->addOption('user-class', null, InputOption::VALUE_REQUIRED, 'User class')
            ->addOption('user-name', null, InputOption::VALUE_REQUIRED, 'User name')
            ->addOption('object-class', null, InputOption::VALUE_REQUIRED, 'Object class')
            ->addOption('object-id', null, InputOption::VALUE_REQUIRED, 'Object identifier')
            ->addOption('object-field', null, InputOption::VALUE_REQUIRED, 'Object field name')
            ->addOption('mask', null, InputOption::VALUE_REQUIRED, 'Mask code (VIEW, CREATE, EDIT, DELETE etc. See all codes in MaskBuilder class)');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ss = new SymfonyStyle($input, $output);

        $userClass = $input->getOption('user-class');
        $userName  = $input->getOption('user-name');

        if (null === $userClass || null === $userName) {
            [$userClass, $userName] = explode('-', $ss->choice('Choose a user', $this->loadSids()), 2);
        }
        if (null === $objectClass = $input->getOption('object-class')) {
            $objectClass = $ss->choice('Choose an object class', $this->loadClasses());
        }
        if (null === $objectIdentifier = $input->getOption('object-id')) {
            $objectIdentifier = $ss->ask('Enter an object identifier', 'class');
        }
        if (null === $objectField = $input->getOption('object-field')) {
            $objectField = $ss->ask('Enter a field name or press Enter to skip', false) ?: null;
        }
        if (null === $maskCode = $input->getOption('mask')) {
            $maskCode = $ss->choice('Choose a mask code', $this->loadMaskCodes());
        }

        $ss->listing(["User class: " . $userClass,
                      "User name: " . $userName,
                      "Object class: " . $objectClass,
                      "Object identifier: " . $objectIdentifier,
                      "Object field: " . $objectField,
                      "Mask: " . $maskCode]);

        if ($ss->confirm("Confirm ACL")) {

            $oid  = new ObjectIdentity($objectIdentifier, $objectClass);
            $sid  = new UserSecurityIdentity($userName, $userClass);
            $mask = (new MaskBuilder())->resolveMask($maskCode);

            try {
                $acl = $this->aclProvider->findAcl($oid);
            } catch (AclNotFoundException $e) {
                $acl = $this->aclProvider->createAcl($oid);
            }

            if ($objectField !== null) {
                $acl->insertClassFieldAce($objectField, $sid, $mask);
            } else {
                $acl->insertClassAce($sid, $mask);
            }

            $this->aclProvider->updateAcl($acl);

            $ss->info('Completed successfully!');
        }

        return 0;
    }

    private function loadSids(): array
    {
        return $this->aclConnection
            ->executeQuery('SELECT identifier FROM ' . $this->aclConnection->quoteIdentifier($this->aclTables['table_sid']))
            ->fetchAll(FetchMode::COLUMN);
    }

    private function loadClasses(): array
    {
        return $this->aclConnection
            ->executeQuery('SELECT class_type FROM ' . $this->aclConnection->quoteIdentifier($this->aclTables['table_classes']))
            ->fetchAll(FetchMode::COLUMN);
    }

    private function loadMaskCodes(): array
    {
        return array_map(
            fn(string $name) => substr($name, 5),
            array_filter(
                array_keys(
                    (new \ReflectionClass(MaskBuilder::class))->getConstants()
                ),
                fn(string $name) => StringType::startsWith($name, 'MASK_')
            )
        );
    }
}

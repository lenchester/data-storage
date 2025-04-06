<?php

namespace App\Controller\Admin;

use App\Entity\File;
use App\Entity\User;
use App\Field\FileField;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class FileCrudController extends AbstractCrudController
{
    public function __construct(private Security $security)
    {
    }

    public static function getEntityFqcn(): string
    {
        return File::class;
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            FileField::new('storedName')
                ->setBasePath('uploads/files')
                ->setUploadDir('public/uploads/files')
                ->setFormTypeOption('upload_filename', '[randomhash]-[slug].[extension]'),
            TextField::new('storedName'),
            TextField::new('extension')
        ];
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof File) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new LogicException('Authenticated user is not a valid App\Entity\User.');
        }
        $entityInstance->setUser($user);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('User must be logged in.');
        }

        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.user = :user')
            ->setParameter('user', $user)
            ->orderBy('entity.createdAt', 'ASC');

        return $qb;
    }
}

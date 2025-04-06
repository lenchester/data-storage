<?php

namespace App\Controller\Admin;

use App\Entity\File;
use App\Entity\User;
use App\Field\FileField;
use App\Service\FileService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use LogicException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class FileCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly FileService $fileService
    )
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
                ->setFormTypeOption('upload_filename', '[randomhash]-[slug].[extension]')
                ->setLabel('upload file')->onlyOnForms(),
            TextField::new('originalName')
                ->setLabel('File Name'),
            TextField::new('extension')
                ->setLabel('File Extension')
                ->onlyOnIndex(),
            IntegerField::new('sizeInBytes')
                ->setLabel('File Size')
                ->onlyOnIndex()
                ->formatValue(fn($value, $entity) => $this->fileService->getFileSizeReadable($value)),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $downloadFile = Action::new('downloadFile', 'Download')
            ->linkToCrudAction('downloadFile');

        return $actions
            ->add(Crud::PAGE_INDEX, $downloadFile)
            ->add(Crud::PAGE_DETAIL, $downloadFile);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof File) {
            return;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new LogicException('Authenticated user is not a valid App\Entity\User.');
        }
        $entityInstance->setUser($user);

        if ($entityInstance->getStoredName()) {
            $fileSize = $this->fileService->getFileSizeInBytes($entityInstance->getStoredName());
            if ($fileSize !== null) {
                $entityInstance->setSizeInBytes($fileSize);
            }
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    #[AdminAction(routePath: '/file/download', routeName: 'admin_file_download', methods: ['GET'])]
    public function downloadFile(AdminContext $context): StreamedResponse
    {
        $file = $context->getEntity()->getInstance();

        if (!$file instanceof File) {
            throw new NotFoundHttpException('File not found.');
        }

        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('You must be logged in to download files.');
        }

        return $this->fileService->streamFileForDownload($file);
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new LogicException('Authenticated user is not a valid App\Entity\User.');
        }

        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.user = :user')
            ->setParameter('user', $user)
            ->orderBy('entity.createdAt', 'ASC');

        return $qb;
    }
}

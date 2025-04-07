<?php

namespace App\Controller\Admin;

use App\Entity\File;
use App\Entity\User;
use App\Field\FileField;
use App\Service\File\Builder\FileEntityBuilder;
use App\Service\File\FileService;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_USER')]
class FileCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly FileService $fileService,
        private readonly FileEntityBuilder $fileEntityBuilder
    ) {}

    public static function getEntityFqcn(): string
    {
        return File::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FileField::new('file', 'Upload File')
                ->setUploadDir('public/uploads/files')
                ->setBasePath('uploads/files')
                ->setFormType(FileType::class)
                ->setFormTypeOption('mapped', true)
                ->setFormTypeOption('required', true)
                ->onlyWhenCreating(),

            TextField::new('originalName')
                ->setLabel('File Name')
                ->onlyWhenUpdating()
                ->onlyOnIndex(),
            TextField::new('extension')
                ->setLabel('File Extension')
                ->onlyOnIndex(),
            IntegerField::new('sizeInBytes')
                ->setLabel('File Size')
                ->onlyOnIndex()
                ->formatValue(fn($value, $entity) => $this->fileService->getFileSizeReadable($value)),
            DateTimeField::new('createdAt')
                ->onlyOnIndex()
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
            throw new InvalidArgumentException('No valid file passed.');
        }

        $uploadedFile = $entityInstance->getFile();
        if (!$uploadedFile instanceof UploadedFile) {
            throw new InvalidArgumentException('No valid uploaded file found.');
        }

        $user = $this->getAuthenticatedUserEntity();

        $entityInstance = $this->fileEntityBuilder->build($entityInstance, $uploadedFile, $user);

        parent::persistEntity($entityManager, $entityInstance);
    }

    #[AdminAction(routePath: '/file/download', routeName: 'admin_file_download', methods: ['GET'])]
    public function downloadFile(AdminContext $context): StreamedResponse
    {
        $user = $this->getAuthenticatedUserEntity();

        $file = $context->getEntity()->getInstance();
        if ($file->getUser() !== $user) {
            throw new AccessDeniedHttpException('You do not have permission to download this file.');
        }

        if (!$file instanceof File) {
            throw new NotFoundHttpException('File not found.');
        }

        return $this->fileService->streamFileForDownload($file);
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->fileService->delete($entityInstance->getStoredName());
        parent::deleteEntity($entityManager, $entityInstance);
    }


    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder
    {
        $user = $this->getAuthenticatedUserEntity();

        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.user = :user')
            ->setParameter('user', $user)
            ->orderBy('entity.createdAt', 'ASC');

        return $qb;
    }

    private function getAuthenticatedUserEntity(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw new LogicException('Authenticated user is not an instance of App\Entity\User.');
        }

        return $user;
    }
}

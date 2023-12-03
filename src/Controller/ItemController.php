<?php

namespace App\Controller;

use App\Entity\Item\Effect\EffectType;
use App\Entity\Item\Item;
use App\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly EntityManagerInterface $manager,
        private readonly ItemRepository $collectableRepository,
        private readonly Filesystem $filesystem,
    ) {
    }

    #[Route('/nft/effect-types', methods: ['GET'])]
    public function nftTypes(): Response
    {
        return $this->json(EffectType::keyValue());
    }

    #[Route('/nft/{id}/upload', methods: ['POST'])]
    public function uploadImage(string $id, Request $request): Response
    {
        if (!$request->files->has('image')) {
            return $this->json('No image provided', Response::HTTP_BAD_REQUEST);
        }

        /** @var Item $collectable */
        $collectable = $this->collectableRepository->find($id);

        if ($collectable === null) {
            return $this->json('Item not found', Response::HTTP_NOT_FOUND);
        }

        /** @var UploadedFile $image */
        $image = $request->files->get('image');

        $publicPath = sprintf('collectable/uploads/%s.jpg', Uuid::uuid4());
        $serverPath = sprintf('%s/public/%s', $this->kernel->getProjectDir(), $publicPath);
        $this->filesystem->copy($image->getPathname(), $serverPath);
        $collectable->setImagePublicPath($publicPath);
        $this->manager->flush();
        return $this->json($publicPath);
    }

}

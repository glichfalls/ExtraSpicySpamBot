<?php

namespace App\Controller;

use App\Entity\Collectable\Collectable;
use App\Entity\Collectable\CollectableFactory;
use App\Repository\ChatRepository;
use App\Repository\CollectableRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class CollectableController extends AbstractController
{

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly EntityManagerInterface $manager,
        private readonly CollectableRepository $collectableRepository,
        private readonly ChatRepository $chatRepository,
        private readonly UserRepository $userRepository,
        private readonly Filesystem $filesystem,
    ) {
    }

    #[Route('/nft', methods: ['POST'])]
    public function createCollectable(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $collectable = CollectableFactory::create(
                $data['name'],
                $data['description'],
                $data['tradable'],
                $data['unique']
            );
            $this->manager->persist($collectable);
            if (array_key_exists('chat', $data)) {
                foreach ($data['chat'] as $chatData) {
                    $chat = $this->chatRepository->find($chatData['id']);
                    if (array_key_exists('users', $data) && count($data['users']) > 0) {
                        foreach ($data['users'] as $userData) {
                            $user = $this->userRepository->find($userData['id']);
                            $instance = CollectableFactory::instance(
                                $collectable,
                                $chat,
                                $user,
                                $data['price'] ?? 0,
                            );
                            $collectable->addInstance($instance);
                            $this->manager->persist($instance);
                        }
                    } else {
                        $instance = CollectableFactory::instance(
                            $collectable,
                            $chat,
                            null,
                            $data['price'],
                        );
                        $collectable->addInstance($instance);
                        $this->manager->persist($instance);
                    }
                }
            }
            $this->manager->flush();
            return $this->json([
                $collectable->getId(),
                $collectable->getInstances()->map(fn ($instance) => $instance->getId())->getValues(),
            ]);
        } catch (\Exception $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/nft/{id}', methods: ['PUT'])]
    public function patchCollectable(string $id, Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $collectable = $this->collectableRepository->find($id);
            if ($collectable === null) {
                throw new NotFoundHttpException();
            }
            $collectable->setName($data['name']);
            $collectable->setDescription($data['description']);
            $collectable->setTradeable($data['tradable']);
            $collectable->setUnique($data['unique']);
            $this->manager->flush();
            return $this->json(true);
        } catch (\Exception $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/nft/{id}/upload', methods: ['POST'])]
    public function uploadImage(string $id, Request $request): Response
    {
        if (!$request->files->has('image')) {
            return $this->json('No image provided', Response::HTTP_BAD_REQUEST);
        }

        /** @var Collectable $collectable */
        $collectable = $this->collectableRepository->find($id);

        if ($collectable === null) {
            return $this->json('Collectable not found', Response::HTTP_NOT_FOUND);
        }

        /** @var UploadedFile $image */
        $image = $request->files->get('image');

        $publicPath = sprintf('/collectable/uploads/%s.jpg', $collectable->getId());
        $serverPath = sprintf('%s/public/%s', $this->kernel->getProjectDir(), $publicPath);
        $this->filesystem->copy($image->getPathname(), $serverPath);
        $collectable->setImagePublicPath($publicPath);
        $this->manager->flush();
        return $this->json($publicPath);
    }

}

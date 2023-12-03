<?php

namespace App\Controller;

use App\Entity\Chat\Chat;
use App\Entity\Item\Attribute\ItemRarity;
use App\Entity\Item\Effect\Effect;
use App\Entity\Item\Effect\EffectType;
use App\Entity\Item\Item;
use App\Entity\Item\ItemFactory;
use App\Repository\ChatRepository;
use App\Repository\ItemRepository;
use App\Repository\EffectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class ItemController extends AbstractController
{

    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly EntityManagerInterface $manager,
        private readonly ItemRepository $collectableRepository,
        private readonly ChatRepository $chatRepository,
        private readonly UserRepository $userRepository,
        private readonly EffectRepository $effectRepository,
        private readonly Filesystem $filesystem,
    ) {
    }

    #[Route('/nft/effect-types', methods: ['GET'])]
    public function nftTypes(): Response
    {
        return $this->json(EffectType::keyValue());
    }

    #[Route('/nft', methods: ['POST'])]
    public function createItem(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $collectable = ItemFactory::create(
                $data['name'],
                $data['description'],
                ItemRarity::tryFrom($data['rarity']),
                $data['permanent'],
            );
            $this->manager->persist($collectable);
            if (array_key_exists('chat', $data)) {
                foreach ($data['chat'] as $chatData) {
                    $chat = $this->chatRepository->find($chatData['id']);
                    $this->createInstances($data, $collectable, $chat);
                }
            }
            $this->manager->flush();
            return $this->json([
                $collectable->getId(),
                $collectable->getInstances()->map(fn ($instance) => $instance->getId())->getValues(),
            ]);
        } catch (Exception $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/nft/{id}', methods: ['PUT'])]
    public function patchCollectable(string $id, Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            /** @var Item $collectable */
            $collectable = $this->collectableRepository->find($id);
            if ($collectable === null) {
                throw new NotFoundHttpException();
            }
            $collectable->setName($data['name']);
            $collectable->setDescription($data['description']);
            $collectable->setTradeable($data['tradable']);
            if ($collectable->isUnique()) {
                $collectable->setUnique($data['unique'] ?? true);
            }
            if (array_key_exists('effects', $data)) {
                foreach ($collectable->getEffects() as $effect) {
                    $collectable->removeEffect($effect);
                }
                foreach ($data['effects'] as $effectData) {
                    $effect = $this->effectRepository->find($effectData['id']);
                    $collectable->addEffect($effect);
                }
            }
            $this->manager->flush();
            return $this->json(true);
        } catch (Exception $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
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

    #[Route('/nft/effects', methods: ['POST'])]
    public function createEffect(Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $effect = new Effect();
            $effect->setType($data['type']);
            $effect->setName($data['name']);
            $effect->setDescription($data['description']);
            $effect->setMagnitude($data['magnitude']);
            $effect->setOperator($data['operator']);
            $effect->setPriority($data['priority'] ?? 100);
            $this->manager->persist($effect);
            $this->manager->flush();
            return $this->json($effect->getId());
        } catch (Exception $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/nft/effects/{id}', methods: ['PUT'])]
    public function updateEffect(string $id, Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            /** @var Effect $effect */
            $effect = $this->effectRepository->find($id);
            if ($effect === null) {
                throw new NotFoundHttpException();
            }
            $effect->setType($data['type']);
            $effect->setName($data['name']);
            $effect->setDescription($data['description']);
            $effect->setMagnitude($data['magnitude']);
            $effect->setOperator($data['operator']);
            $effect->setPriority($data['priority']);
            $this->manager->flush();
            return $this->json(true);
        } catch (Exception $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/nft/{id}/instances', methods: ['POST'])]
    public function createInstance(string $id, Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            /** @var Item $collectable */
            $collectable = $this->collectableRepository->find($id);
            if ($collectable === null) {
                throw $this->createNotFoundException();
            }
            $chat = $this->chatRepository->find($data['chat']);
            $this->createInstances($data, $collectable, $chat);
            $this->manager->flush();
            return $this->json(true);
        } catch (Exception $exception) {
            return $this->json($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param mixed $data
     * @param Item $collectable
     * @param Chat $chat
     * @return void
     * @throws Exception
     */
    private function createInstances(mixed $data, Item $collectable, Chat $chat): void
    {
        if (array_key_exists('expiresAt', $data) && $data['expiresAt'] !== null) {
            $expiresAt = new \DateTime($data['expiresAt']);
        } else {
            $expiresAt = null;
        }
        if (array_key_exists('users', $data) && count($data['users']) > 0) {
            foreach ($data['users'] as $userData) {
                $user = $this->userRepository->find($userData['id']);
                $instance = ItemFactory::instance(
                    $collectable,
                    $chat,
                    $user,
                    $data['tradeable'],
                    $expiresAt,
                );
                $collectable->addInstance($instance);
                $this->manager->persist($instance);
            }
        } else {
            $instance = ItemFactory::instance(
                $collectable,
                $chat,
                null,
                $data['tradeable'],
                $expiresAt,
            );
            $collectable->addInstance($instance);
            $this->manager->persist($instance);
        }
    }

}
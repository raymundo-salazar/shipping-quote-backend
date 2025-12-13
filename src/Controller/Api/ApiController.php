<?php

namespace App\Controller\Api;

use App\Api\Exception\BadRequestException;
use App\Api\Exception\MethodNotAllowedApiException;
use App\Api\Exception\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

abstract class ApiController extends AbstractController
{
    /** @var class-string|null */
    protected const ENTITY_CLASS = null;

    /**
     * Métodos CRUD habilitados.
     * Posibles: findAll, findByPK, create, update, delete
     *
     * @var string[]
     */
    protected array $apiMethods = ['findAll', 'findByPK', 'create', 'update', 'delete'];

    /**
     * Campos del body que se pueden escribir (create/update).
     *
     * @var string[]
     */
    protected array $writableFields = [];

    /**
     * Debe ser implementado por cada hijo.
     *
     * @return array<string,mixed>
     */
    abstract protected function transformEntity(object $entity): array;

    protected function getEntityClass(): string
    {
        if (static::ENTITY_CLASS === null) {
            throw new \LogicException('ENTITY_CLASS must be defined in child controller.');
        }

        /** @var class-string $class */
        $class = static::ENTITY_CLASS;

        return $class;
    }

    protected function getRepository(EntityManagerInterface $em): ObjectRepository
    {
        return $em->getRepository($this->getEntityClass());
    }

    protected function assertMethodEnabled(string $method): void
    {
        if (!in_array($method, $this->apiMethods, true)) {
            throw new MethodNotAllowedApiException($method);
        }
    }

    /**
     * Mapea $writableFields → setters.
     *
     * @param array<string,mixed> $data
     */
    protected function hydrateEntity(object $entity, array $data): void
    {
        foreach ($this->writableFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $field)));

            if (method_exists($entity, $setter)) {
                $entity->{$setter}($value);
            }
        }
    }

    // ---------- LIST ----------
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): array
    {
        $this->assertMethodEnabled('findAll');

        $items = $this->getRepository($em)->findAll();

        $result = array_map(
            fn(object $entity) => $this->transformEntity($entity),
            $items
        );

        return [
            'items' => $result,
        ];
    }

    // ---------- SHOW ----------
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): array
    {
        $this->assertMethodEnabled('findByPK');

        $entity = $this->getRepository($em)->find($id);

        if (!$entity) {
            throw new NotFoundException();
        }

        return [
            'item' => $this->transformEntity($entity),
        ];
    }

    // ---------- CREATE ----------
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): array
    {
        $this->assertMethodEnabled('create');

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new BadRequestException('INVALID_JSON', 'Invalid JSON body');
        }

        $class = $this->getEntityClass();
        $entity = new $class();

        $this->hydrateEntity($entity, $data);

        $em->persist($entity);
        $em->flush();

        return [
            'item' => $this->transformEntity($entity),
        ];
    }

    // ---------- UPDATE ----------
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): array
    {
        $this->assertMethodEnabled('update');

        $entity = $this->getRepository($em)->find($id);

        if (!$entity) {
            throw new NotFoundException();
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            throw new BadRequestException('INVALID_JSON', 'Invalid JSON body');
        }

        $this->hydrateEntity($entity, $data);

        $em->flush();

        return [
            'item' => $this->transformEntity($entity),
        ];
    }

    // ---------- DELETE ----------
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): array
    {
        $this->assertMethodEnabled('delete');

        $entity = $this->getRepository($em)->find($id);

        if (!$entity) {
            throw new NotFoundException();
        }

        $em->remove($entity);
        $em->flush();

        return [
            'deleted' => true,
            'id' => $id,
        ];
    }
}

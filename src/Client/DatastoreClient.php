<?php

namespace Appsero\LaravelDatastore\Client;

use DomainException;
use Google\Cloud\Datastore\DatastoreClient as BaseDatastoreClient;
use Google\Cloud\Datastore\EntityInterface;
use Google\Cloud\Datastore\Key;

class DatastoreClient extends BaseDatastoreClient
{
    /**
     * Insert an entity
     *
     * An entity with incomplete keys will be allocated an ID prior to insertion.
     *
     * Insert by this method is non-transactional. If you need transaction
     * support, use {@see Google\Cloud\Datastore\Transaction::insert()}.
     *
     * Example:
     * ```
     * $key = $datastore->key('Person', 'Bob');
     * $entity = $datastore->entity($key, ['firstName' => 'Bob']);
     *
     * $datastore->insert($entity);
     * ```
     *
     * @see https://cloud.google.com/datastore/docs/reference/rest/v1/projects/commit Commit API documentation
     *
     * @param EntityInterface $entity The entity to be inserted.
     * @param array $options [optional] Configuration options.
     * @return Key
     * @throws DomainException If a conflict occurs, fail.
     */
    public function insert(EntityInterface $entity, array $options = [])
    {
        return $this->insertBatch([$entity], $options)[0];
    }

    /**
     * Insert multiple entities
     *
     * Any entity with incomplete keys will be allocated an ID prior to insertion.
     *
     * Insert by this method is non-transactional. If you need transaction
     * support, use {@see Google\Cloud\Datastore\Transaction::insertBatch()}.
     *
     * Example:
     * ```
     *
     * $entities = [
     *     $datastore->entity('Person', ['firstName' => 'Bob']),
     *     $datastore->entity('Person', ['firstName' => 'John'])
     * ];
     *
     * $datastore->insertBatch($entities);
     * ```
     *
     * @see https://cloud.google.com/datastore/docs/reference/rest/v1/projects/commit Commit API documentation
     *
     * @param EntityInterface[] $entities The entities to be inserted.
     * @param array $options [optional] Configuration options.
     * @return []Key
     */
    public function insertBatch(array $entities, array $options = [])
    {
        $mutations = [];

        foreach ($entities as $entity) {
            $mutations[] = $this->operation->mutation('insert', $entity, Entity::class);
        }

        $result = $this->operation->commit($mutations, $options);

        return $this->handleMutationResult($entities, $result);
    }

    /**
     * Update an entity
     *
     * Please note that updating a record in Cloud Datastore will replace the
     * existing record. Adding, editing or removing a single property is only
     * possible by first retrieving the entire entity in its existing state.
     *
     * Update by this method is non-transactional. If you need transaction
     * support, use {@see Google\Cloud\Datastore\Transaction::update()}.
     *
     * Example:
     * ```
     * $entity['firstName'] = 'John';
     *
     * $datastore->update($entity);
     * ```
     *
     * @see https://cloud.google.com/datastore/docs/reference/rest/v1/projects/commit Commit API documentation
     *
     * @param EntityInterface $entity The entity to be updated.
     * @param array $options [optional] {
     *     Configuration Options
     *
     *     @type bool $allowOverwrite Entities must be updated as an entire
     *           resource. Patch operations are not supported. Because entities
     *           can be created manually, or obtained by a lookup or query, it
     *           is possible to accidentally overwrite an existing record with a
     *           new one when manually creating an entity. To provide additional
     *           safety, this flag must be set to `true` in order to update a
     *           record when the entity provided was not obtained through a
     *           lookup or query. **Defaults to** `false`.
     * }
     * @return Key
     * @throws DomainException If a conflict occurs, fail.
     */
    public function update(EntityInterface $entity, array $options = [])
    {
        return $this->updateBatch([$entity], $options)[0];
    }

    /**
     * Update multiple entities
     *
     * Please note that updating a record in Cloud Datastore will replace the
     * existing record. Adding, editing or removing a single property is only
     * possible by first retrieving the entire entity in its existing state.
     *
     * Update by this method is non-transactional. If you need transaction
     * support, use {@see Google\Cloud\Datastore\Transaction::updateBatch()}.
     *
     * Example:
     * ```
     * $entities[0]['firstName'] = 'Bob';
     * $entities[1]['firstName'] = 'John';
     *
     * $datastore->updateBatch($entities);
     * ```
     *
     * @see https://cloud.google.com/datastore/docs/reference/rest/v1/projects/commit Commit API documentation
     *
     * @param EntityInterface[] $entities The entities to be updated.
     * @param array $options [optional] {
     *     Configuration Options
     *
     *     @type bool $allowOverwrite Entities must be updated as an entire
     *           resource. Patch operations are not supported. Because entities
     *           can be created manually, or obtained by a lookup or query, it
     *           is possible to accidentally overwrite an existing record with a
     *           new one when manually creating an entity. To provide additional
     *           safety, this flag must be set to `true` in order to update a
     *           record when the entity provided was not obtained through a
     *           lookup or query. **Defaults to** `false`.
     * }
     * @return []Key
     */
    public function updateBatch(array $entities, array $options = [])
    {
        $result = parent::updateBatch($entities, $options);
        return $this->handleMutationResult($entities, $result);
    }

    /**
     * Upsert an entity
     *
     * Upsert will create a record if one does not already exist, or overwrite
     * existing record if one already exists.
     *
     * Please note that upserting a record in Cloud Datastore will replace the
     * existing record, if one exists. Adding, editing or removing a single
     * property is only possible by first retrieving the entire entity in its
     * existing state.
     *
     * An entity with incomplete keys will be allocated an ID prior to insertion.
     *
     * Upsert by this method is non-transactional. If you need transaction
     * support, use {@see Google\Cloud\Datastore\Transaction::upsert()}.
     *
     * Example:
     * ```
     * $key = $datastore->key('Person', 'Bob');
     * $entity = $datastore->entity($key, ['firstName' => 'Bob']);
     *
     * $datastore->upsert($entity);
     * ```
     *
     * @see https://cloud.google.com/datastore/docs/reference/rest/v1/projects/commit Commit API documentation
     *
     * @param EntityInterface $entity The entity to be upserted.
     * @param array $options [optional] Configuration Options.
     * @return Key The entity key
     * @throws DomainException If a conflict occurs, fail.
     */
    public function upsert(EntityInterface $entity, array $options = [])
    {
        return $this->upsertBatch([$entity], $options)[0];
    }

    /**
     * Upsert multiple entities
     *
     * Upsert will create a record if one does not already exist, or overwrite
     * an existing record if one already exists.
     *
     * Please note that upserting a record in Cloud Datastore will replace the
     * existing record, if one exists. Adding, editing or removing a single
     * property is only possible by first retrieving the entire entity in its
     * existing state.
     *
     * Any entity with incomplete keys will be allocated an ID prior to insertion.
     *
     * Upsert by this method is non-transactional. If you need transaction
     * support, use {@see Google\Cloud\Datastore\Transaction::upsertBatch()}.
     *
     * Example:
     * ```
     * $keys = [
     *     $datastore->key('Person', 'Bob'),
     *     $datastore->key('Person', 'John')
     * ];
     *
     * $entities = [
     *     $datastore->entity($keys[0], ['firstName' => 'Bob']),
     *     $datastore->entity($keys[1], ['firstName' => 'John'])
     * ];
     *
     * $datastore->upsertBatch($entities);
     * ```
     *
     * @see https://cloud.google.com/datastore/docs/reference/rest/v1/projects/commit Commit API documentation
     *
     * @param EntityInterface[] $entities The entities to be upserted.
     * @param array $options [optional] Configuration Options.
     * @return []Key array of keys updated
     */
    public function upsertBatch(array $entities, array $options = [])
    {
        $mutations = [];
        
        foreach ($entities as $entity) {
            if ($entity->key()->state() === Key::STATE_INCOMPLETE) {
                $mutations[] = $this->operation->mutation('insert', $entity, Entity::class);
            } else {
                $mutations[] = $this->operation->mutation('upsert', $entity, Entity::class);
            }
        }

        $result = $this->operation->commit($mutations, $options);

        return $this->handleMutationResult($entities, $result);
    }

    /**
     * Handle mutation results
     */
    protected function handleMutationResult(array $entities, array $result)
    {
        $keys = [];

        foreach ($entities as $index => $entity) {
            if ($entity->key()->state() === Key::STATE_INCOMPLETE) {
                $key = $result['mutationResults'][$index]['key'];
                
                $end = end($key['path']);
                $id = $end['id'];
                
                $entity->key()->setLastElementIdentifier($id);
            }

            $keys[] = $entity->key();
        }

        return $keys;
    }
}
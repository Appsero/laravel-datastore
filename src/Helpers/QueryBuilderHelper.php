<?php

namespace Appsero\LaravelDatastore\Helpers;

use Google\Cloud\Datastore\Key;
use Illuminate\Support\Arr;

trait QueryBuilderHelper
{
    /**
     * @inheritdoc
     */
    public function find($id, $columns = [])
    {
        return $this->lookup($id, $columns);
    }

    /**
     * Retrieve a single entity using key.
     */
    public function lookup($id, $columns = [])
    {
        $key = $this->getClient()->key($this->from, $id);

        $result = $this->getClient()->lookup($key);

        if (empty($result)) {
            return null;
        }

        $result = $this->processor->processSingleResult($this, $result);

        return empty($columns) ? $result : (object) Arr::only((array) $result, Arr::wrap($columns));
    }

    /**
     * @inheritdoc
     */
    public function get($columns = ['*'])
    {
        if (!empty($columns)) {
            $this->addSelect($columns);
        }

        // Drop all columns if * is present.
        if (in_array('*', $this->columns)) {
            $this->columns = [];
        }

        $query = $this->getClient()->query()->kind($this->from)
            ->projection($this->columns)
            ->offset($this->offset)
            ->limit($this->limit);

        if ($this->keysOnly) {
            $query->keysOnly();
        }

        if (is_array($this->wheres) && count($this->wheres)) {
            foreach ($this->wheres as $filter) {
                if ($filter['type'] == 'Basic') {
                    $query->filter($filter['column'], $filter['operator'], $filter['value']);
                }
            }
        }

        if (is_array($this->orders) && count($this->orders)) {
            foreach ($this->orders as $order) {
                $query->order($order['column'], $order['direction']);
            }
        }

        $results = $this->getClient()->runQuery($query);

        return $this->processor->processResults($this, $results);
    }

    /**
     * Key Only Query.
     */
    public function getKeys()
    {
        return $this->keys()->get()->pluck('_keys');
    }

    /**
     * Key Only Query.
     */
    public function keysOnly()
    {
        return $this->getKeys();
    }

    /**
     * @inheritdoc
     */
    public function delete($key = null)
    {
        if (is_null($key)) {
            $keys = $this->keys()->get()->pluck('__key__')->toArray();
        } else {
            if ($key instanceof Key || (is_array($key) && $key[0] instanceof Key) || empty($this->from)) {
                $keys = Arr::wrap($key);
            } else {
                if (is_array($key)) {
                    $keys = array_map(function ($item) {
                        return $item instanceof Key ? $item : $this->getClient()->key($this->from, $item);
                    }, $key);
                } else {
                    $keys = [$this->getClient()->key($this->from, $key)];
                }

                return $keys;
            }
        }

        return $this->getClient()->deleteBatch($keys);
    }
    /**
     * @inheritdoc
     */
    public function insert(array $values, $key = '', $options = [])
    {
        if (empty($this->from)) {
            throw new \LogicException('No kind/table specified');
        }

        if (empty($values)) {
            return true;
        }

        $key = $key ? $this->getClient()->key($this->from, $key) : $this->getClient()->key($this->from);

        $entity = $this->getClient()->entity($key, $values, $options);

        $result = $this->getClient()->insert($entity);

		if (is_numeric($result) && $result > 0) {
			$entity = $values;
			$entity['id'] = $entity['id'] ?? $key->path()[0]['name'] ?? $key->path()[0]['id'];
			$entity['_key'] = $key->path()[0];
			$entity['_keys'] = $key->path();
			$entity['__key__'] = $key;

			return (object) $entity;
		}

		return null;
    }

    /**
     * @inheritdoc
     */
    public function update(array $values, $key = '', $options = [])
    {
        $this->applyBeforeQueryCallbacks();
        return $this->upsert($values, $key, $options);
    }

    /**
     * @inheritdoc
     */
    public function upsert(array $values, $key = '', $options = [])
    {
        
        if (empty($this->from)) {
            throw new \LogicException('No kind/table specified');
        }

        if (empty($values)) {
            return true;
        }
        foreach($this->wheres as $where):
            if($where['value'] instanceof \Google\Cloud\Datastore\Key):
                $key = $where['value'];
                break;
            endif;
        endforeach;

        if($key instanceof \Google\Cloud\Datastore\Key):
            $entity = $this->getClient()->lookup($key);

            if($entity == null):
                $entity = $this->getClient()->entity($key, $values, $options);
            endif;

            foreach($values as $key=>$value):
                $entity->$key = $value;
            endforeach;
        else:
            $key = $key ? $this->getClient()->key($this->from, $key) : $this->getClient()->key($this->from);
            $entity = $this->getClient()->entity($key, $values, $options);
        endif;

        return $this->getClient()->upsert($entity);
    }
}

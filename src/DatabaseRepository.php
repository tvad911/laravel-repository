<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace anlutro\LaravelRepository;

use anlutro\LaravelValidation\ValidatorInterface;
use Illuminate\Database\Connection;
use Illuminate\Support\Fluent;

/**
 * Abstract database repository pattern. Use it to build repositories that don't
 * utilize Eloquent for simplicity or performance reasons.
 */
abstract class DatabaseRepository extends AbstractRepository
{
	/**
	 * The database connection to use.
	 *
	 * @var \Illuminate\Database\Connection
	 */
	protected $db;

	/**
	 * The table to run queries from.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The primary key of the table.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * @param \Illuminate\Database\Connection $db
	 * @param \anlutro\LaravelValidation\ValidatorInterface $validator
	 */
	public function __construct(Connection $db, ValidatorInterface $validator = null)
	{
		parent::__construct($validator);

		if ($this->table === null) {
			$class = get_class($this);
			throw new \RuntimeException("Property {$class}::\$table must be defined.");
		}

		$this->setConnection($db);

		if ($validator) {
			$validator->replace('table', $this->table);
		}
	}

	/**
	 * Set the connection to run queries on.
	 *
	 * @param \Illuminate\Database\Connection $db
	 */
	public function setConnection(Connection $db)
	{
		$this->db = $db;

		return $this;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getConnection()
	{
		return $this->db;
	}

	/**
	 * Set the table to query from.
	 *
	 * @param  string $table
	 *
	 * @return $this
	 */
	public function setTable($table)
	{
		$this->table = (string) $table;

		return $this;
	}

	/**
	 * Get the table that's being queried from.
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getKeyName()
	{
		return "{$this->table}.{$this->primaryKey}";
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return  \Illuminate\Support\Fluent
	 */
	public function getNew(array $attributes = array())
	{
		return new Fluent($attributes);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return  \Illuminate\Support\Fluent|false
	 */
	protected function performCreate($entity, array $attributes = array())
	{
		$this->fillEntityAttributes($entity, $attributes);

		$result = $this->newQuery()
			->insertGetId($this->getEntityAttributes($entity));

		if ($result) {
			$this->setEntityKey($entity, $result);
			return $entity;
		}

		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function performUpdate($entity, array $attributes)
	{
		$this->fillEntityAttributes($entity, $attributes);

		return (bool) $this->newQuery()
			->where($this->getKeyName(), '=', $this->getEntityKey($entity))
			->update($this->getEntityAttributes($entity));
	}

	/**
	 * {@inheritdoc}
	 */
	protected function performDelete($entity)
	{
		return (bool) $this->newQuery()
			->where($this->getKeyName(), '=', $this->getEntityKey($entity))
			->delete();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function newQuery()
	{
		return $this->db->table($this->table);
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getEntityKey($entity)
	{
		return $entity->{$this->primaryKey};
	}

	protected function setEntityKey($entity, $key)
	{
		$entity->{$this->primaryKey} = $key;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function getEntityAttributes($entity)
	{
		return $entity->getAttributes();
	}

	/**
	 * Fill an entity's attributes.
	 *
	 * @param  mixed  $entity
	 * @param  array  $attributes
	 *
	 * @return void
	 */
	protected function fillEntityAttributes($entity, array $attributes)
	{
		foreach ($attributes as $key => $value) {
			$entity->$key = $value;
		}
	}

	protected function getRegularQueryResults($query, $many)
	{
		$results = parent::getRegularQueryResults($query, $many);

		if ($many) {
			return array_map([$this, 'getNew'], $results);
		} else {
			return $results ? $this->getNew($results) : $results;
		}
	}

	protected function getPaginatedQueryResults($query)
	{
		$paginator = parent::getPaginatedQueryResults();

		$paginator->setItems(array_map([$this, 'getNew'], $paginator->setItems()));

		return $paginator;
	}

	public function persist($entity)
	{
		if ($key = $this->getEntityKey($entity)) {
			return $this->update($entity, []);
		} else {
			return (bool) $this->perform('create', $entity, [], true);
		}
	}
}

<?php
/**
 * Laravel 4 Repository classes
 *
 * @author   Andreas Lutro <anlutro@gmail.com>
 * @license  http://opensource.org/licenses/MIT
 * @package  l4-repository
 */

namespace c;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;

/**
 * Abstract Eloquent repository that provides some basic functionality.
 */
abstract class EloquentRepository extends AbstractRepository
{
	/**
	 * @var Illuminate\Database\Eloquent\Model
	 */
	protected $model;

	/**
	 * Whether to call push() or just save() when creating/updating a model.
	 *
	 * @var boolean
	 */
	protected $push = false;

	/**
	 * @param Illuminate\Database\Eloquent\Model $model
	 * @param c\Validator $validator
	 */
	public function __construct(Model $model, Validator $validator = null)
	{
		parent::__construct();

		$this->model = $model;

		if ($validator) {
			$this->validator = $validator;
			$this->validator->replace('table', $this->model->getTable());
		}
	}

	/**
	 * Get the repository's model.
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * Set the repository's model.
	 *
	 * @param $model  Illuminate\Database\Eloquent\Model
	 */
	public function setModel($model)
	{
		$this->model = $model;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNew(array $attributes = array())
	{
		return $this->model->newInstance($attributes);
	}

	/**
	 * {@inheritdoc}
	 */
	public function update($model, array $attributes)
	{
		if (!$model->exists) {
			throw new \RuntimeException('Cannot update non-existant model');
		}

		return parent::update($model, $attributes);
	}

	/**
	 * {@inheritdoc}
	 */
	public function performCreate($object, array $attributes)
	{
		return $this->perform('save', $object, $attributes, false);
	}

	/**
	 * {@inheritdoc}
	 */
	public function performUpdate($object, array $attributes)
	{
		return $this->perform('save', $object, $attributes, false);
	}

	/**
	 * {@inheritdoc}
	 */
	public function performSave($model, array $attributes)
	{
		$method = $this->push ? 'push' : 'save';
		return $model->fill($attributes)->$method() ? $model : false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function performDelete($model)
	{
		return $model->delete();
	}

	/**
	 * {@inheritdoc}
	 */
	public function newQuery()
	{
		return $this->model->newQuery();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getKeyName()
	{
		return $this->model->getQualifiedKeyName();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getEntityKey($model)
	{
		return $model->getKey();
	}
}

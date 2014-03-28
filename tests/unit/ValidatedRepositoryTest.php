<?php
namespace anlutro\LaravelRepository\Tests;

use PHPUnit_Framework_TestCase;
use Mockery as m;

class ValidatedRepositoryTest extends PHPUnit_Framework_TestCase
{
	/** @test */
	public function errorsAreAddedAndCanBeRetrieved()
	{
		$model = m::mock('Illuminate\Database\Eloquent\Model');
		$model->shouldReceive('getTable')->once()->andReturn('table');
		$validator = m::mock('anlutro\LaravelValidation\Validator');
		$validator->shouldReceive('replace')->once()->with('table', 'table');
		$repo = new ValidatedRepositoryStub($model, $validator);

		$model->shouldReceive('newInstance->fill->save')->once()->andReturn(true);
		$validator->shouldReceive('validCreate')->andReturn(false);
		$validator->shouldReceive('errors')->andReturn(new \Illuminate\Support\MessageBag(['error' => ['message']]));
		$repo->create(['foo' => 'bar']);
		$errors = $repo->getErrors();
		$this->assertInstanceOf('Illuminate\Support\MessageBag', $errors);
		$this->assertEquals(['error' => ['message']], $errors->getMessages());
	}
}

class ValidatedRepositoryStub extends \anlutro\LaravelRepository\EloquentRepository {}

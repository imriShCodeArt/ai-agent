<?php
namespace AIAgent\Domain\Contracts;

interface RepositoryInterface {
	/**
	 * @return mixed
	 */
	public function findById(int $id);
}
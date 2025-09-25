<?php
namespace AIAgent\\Domain\\Contracts;

interface RepositoryInterface {
public function findById(int $id);
}
<?php

/*
 * LibDMF
 *
 * Copyright (C) 2018 SOFe
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types=1);

namespace SOFe\LibDMF;

use function array_filter;
use InvalidStateException;
use function assert;
use function bin2hex;
use function hash;
use function microtime;
use function random_bytes;
use function strlen;
use function substr;

abstract class DataModel{
	// TYPE
	/** @var DataModelConfig */
	private $config;
	/** @var string */
	private $type;

	/** @var ExecutionQueue */
	protected $syncQueue;

	// UNIQUE
	/** @var string */
	private $doid;

	// VOLATILE
	/** @var float */
	private $lastAccess;
	/** @var bool */
	private $garbage = false;

	// REFERENCABLE
	/** @var DataModelUser[] */
	private $users = [];

	/** @var bool */
	private $updating = false;

	/** @var bool */
	private $new;
	/** @var string */
	private $lastState;


	protected function __construct(DataModelConfig $config, string $type, bool $new, string $doid = null){
		// basic
		$this->config = $config;
		$this->type = $type;
		$this->syncQueue = new ExecutionQueue();

		// unique
		if($doid === null){
			assert($new, "Loaded DataModel must be initialized with DOID");
			$this->doid = self::generateDoid($type, $config->getDoidFragmentSizes());
		}else{
			$this->doid = $doid;
		}

		// volatile
		$this->lastAccess = microtime(true);

		$this->new = $new;
	}

	public function getConfig() : DataModelConfig{
		return $this->config;
	}

	public function getType() : string{
		return $this->type;
	}

	public function getDoid() : string{
		return $this->doid;
	}

	private static function generateDoid(string $type, array $fragments) : string{
		$c = hash("crc32b", $type, true);
		assert(strlen($c) === 4);

		$out = bin2hex(substr($c, 0, 2) ^ substr($c, 2, 2));
		foreach($fragments as $fragment){
			$out .= "-";
			$out .= bin2hex(random_bytes($fragment));
		}
		return $out;
	}


	public function getLastAccess() : float{
		return $this->lastAccess;
	}

	public function isGarbage() : bool{
		return $this->garbage;
	}

	/**
	 * @return DataModelUser[]
	 */
	public function getUsers() : array{
		return $this->users;
	}

	public function addUser(DataModelUser $user) : void{
		if($this->garbage){
			throw new InvalidStateException("Cannot access garbage DataModel");
		}
		$this->users[] = $user;
	}

	public function shouldGarbage() : bool{
		if(microtime(true) - $this->lastAccess < $this->config->getLingerTime()){
			return false;
		}
		$this->users = array_filter($this->users, function(DataModelUser $user) : bool{
			return $user->isActive($this);
		});
		return $this->garbage = empty($this->users);
	}


	public function updateUnsafe(callable $always, ?callable $onReject = null) : void{
		if($this->garbage){
			throw new InvalidStateException("Cannot access garbage DataModel");
		}
		$this->syncQueue->add(function() use ($always, $onReject) : void{
			$this->updating = true;
			$always();
			$this->updating = false;
			$this->sendBatch($onReject);
		});
	}

	public function updateSafe(callable $temp, ?callable $onReject = null) : void{
		if($this->garbage){
			throw new InvalidStateException("Cannot access garbage DataModel");
		}
		$state = $this->lastState;
		$this->syncQueue->add(function() use ($temp, $onReject, $state) : void{
			$this->updating = true;
			if($this->lastState === $state){
				$temp();
			}else{
				$onReject();
			}
			$this->updating = false;
			if($this->lastState === $state){
				$this->sendBatch($onReject);
			}
		});
	}

	private function sendBatch(callable $onReject) : void{
		// TODO implement
	}


	protected function onGet() : void{
		if($this->garbage){
			throw new InvalidStateException("Cannot access garbage DataModel");
		}
		$this->lastAccess = microtime(true);
	}

	protected function onSet() : void{
		if(!$this->updating){
			throw new InvalidStateException("Setters must be called inside an update() block");
		}
	}

	public function getLastState() : string{
		return $this->lastState;
	}
}

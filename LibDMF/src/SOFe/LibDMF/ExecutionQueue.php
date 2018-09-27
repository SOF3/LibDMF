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

use SplQueue;
use function assert;

class ExecutionQueue{
	/** @var SplQueue */
	private $queue;
	/** @var callable|null */
	private $executing = null;
	/** @var bool */
	private $immediate = false;

	public function __construct(){
		$this->queue = new SplQueue();
	}

	public function add(callable $c) : void{
		if($this->executing !== null){
			$this->queue->enqueue($c);
			return;
		}

		$this->next($c);
	}

	private function next(callable $c) : void{
		while(true){
			$this->executing = $c;
			$this->immediate = true;
			$c(function() use ($c) : void{
				assert($c === $this->executing);
				$this->executing = null;
				if($this->immediate){
					$this->immediate = false;
				}elseif(!$this->queue->isEmpty()){
					$this->next($this->queue->dequeue());
				}
			});
			if($this->immediate || $this->queue->isEmpty()){
				break;
			}
		}
		$this->immediate = false;
	}
}

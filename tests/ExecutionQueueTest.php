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

use PHPUnit\Framework\TestCase;

class ExecutionQueueTest extends TestCase{
	protected $called = 0;

	/** @var callable[] */
	protected $callLater;

	public function testImmediate() : void{
		$queue = new ExecutionQueue();
		$queue->add([$this, "callbackImmediate"]);
		self::assertEquals(1, $this->called);
	}

	public function testLater() : void{
		$queue = new ExecutionQueue();
		$queue->add([$this, "callbackLater"]);
		self::assertEquals(0, $this->called);
		$this->callLater();
		self::assertEquals(1, $this->called);
	}

	public function testImmediateImmediate() : void{
		$queue = new ExecutionQueue();
		$queue->add([$this, "callbackImmediate"]);
		self::assertEquals(1, $this->called);
		$queue->add([$this, "callbackImmediate"]);
		self::assertEquals(2, $this->called);
	}

	public function testImmediateLater() : void{
		$queue = new ExecutionQueue();
		$queue->add([$this, "callbackImmediate"]);
		self::assertEquals(1, $this->called);
		$queue->add([$this, "callbackLater"]);
		self::assertEquals(1, $this->called);
		$this->callLater();
		self::assertEquals(2, $this->called);
	}

	public function testLaterImmediateUnstacked() : void{
		$queue = new ExecutionQueue();
		$queue->add([$this, "callbackLater"]);
		self::assertEquals(0, $this->called);
		$this->callLater();
		self::assertEquals(1, $this->called);
		$queue->add([$this, "callbackImmediate"]);
		self::assertEquals(2, $this->called);
	}

	public function testLaterLaterUnstacked() : void{
		$queue = new ExecutionQueue();
		$queue->add([$this, "callbackLater"]);
		self::assertEquals(0, $this->called);
		$this->callLater();
		self::assertEquals(1, $this->called);
		$queue->add([$this, "callbackLater"]);
		self::assertEquals(1, $this->called);
		$this->callLater();
		self::assertEquals(2, $this->called);
	}

	public function testLaterImmediateStacked() : void{
		$queue = new ExecutionQueue();
		$queue->add([$this, "callbackLater"]);
		self::assertEquals(0, $this->called);
		$queue->add([$this, "callbackImmediate"]);
		self::assertEquals(0, $this->called);
		$this->callLater();
		self::assertEquals(2, $this->called);
	}

	public function testLaterLaterStacked() : void{
		$queue = new ExecutionQueue();
		$queue->add([$this, "callbackLater"]);
		self::assertEquals(0, $this->called);
		$this->callLater();
		self::assertEquals(1, $this->called);
		$queue->add([$this, "callbackLater"]);
		self::assertEquals(1, $this->called);
		$this->callLater();
		self::assertEquals(2, $this->called);
	}

	public function callLater() : void{
		$later = $this->callLater;
		$this->callLater = [];
		foreach($later as $c){
			$c();
		}
	}

	protected function tearDown() : void{
		self::assertEmpty($this->callLater);
		$this->called = 0;
	}

	public function callbackImmediate(callable $done) : void{
		$this->called++;
		$done();
	}

	public function callbackLater(callable $done) : void{
		$this->callLater[] = function() use ($done){
			$this->called++;
			$done();
		};
	}
}

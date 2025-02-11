<?php

/*
 * This file is part of the Silex framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Silex\Tests;

use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Silex\CallbackResolver;

class CallbackResolverTest extends Testcase
{
    private $app;
    private $resolver;

    public function setup(): void
    {
        $this->app = new Container();
        $this->resolver = new CallbackResolver($this->app);
    }

    public function testShouldResolveCallback()
    {
        $callable = function () {
        };
        $this->app['some_service'] = function () {
            return new \ArrayObject();
        };
        $this->app['callable_service'] = function () use ($callable) {
            return $callable;
        };

        $this->assertTrue($this->resolver->isValid('some_service:methodName'));
        $this->assertTrue($this->resolver->isValid('callable_service'));
        $this->assertEquals(
            [$this->app['some_service'], 'append'],
            $this->resolver->convertCallback('some_service:append')
        );
        $this->assertSame($callable, $this->resolver->convertCallback('callable_service'));
    }

    /**
     * @dataProvider nonStringsAreNotValidProvider
     */
    public function testNonStringsAreNotValid($name)
    {
        $this->assertFalse($this->resolver->isValid($name));
    }

    public function nonStringsAreNotValidProvider()
    {
        return [
            [null],
            ['some_service::methodName'],
            ['missing_service'],
        ];
    }

    /**
     *
     *
     * @dataProvider shouldThrowAnExceptionIfServiceIsNotCallableProvider
     */
    public function testShouldThrowAnExceptionIfServiceIsNotCallable($name)
    {
        $this->expectExceptionMessageMatches("/Service \"[a-z_]+\" is not callable./");
        $this->expectException(\InvalidArgumentException::class);
        $this->app['non_callable_obj'] = function () {
            return new \stdClass();
        };
        $this->app['non_callable'] = function () {
            return [];
        };
        $this->resolver->convertCallback($name);
    }

    public function shouldThrowAnExceptionIfServiceIsNotCallableProvider()
    {
        return [
            ['non_callable_obj:methodA'],
            ['non_callable'],
        ];
    }
}

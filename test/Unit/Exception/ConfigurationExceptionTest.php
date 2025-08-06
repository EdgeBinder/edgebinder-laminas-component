<?php

declare(strict_types=1);

namespace EdgeBinder\Component\Test\Exception;

use EdgeBinder\Component\Exception\ConfigurationException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test case for ConfigurationException class.
 */
#[CoversClass(ConfigurationException::class)]
final class ConfigurationExceptionTest extends TestCase
{
    public function testExtendsInvalidArgumentException(): void
    {
        $exception = ConfigurationException::missingConfiguration('test reason');

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(ConfigurationException::class, $exception);
    }

    public function testMissingConfiguration(): void
    {
        $reason = 'config service not found in container';
        $exception = ConfigurationException::missingConfiguration($reason);

        $this->assertSame(
            'EdgeBinder configuration is missing: config service not found in container',
            $exception->getMessage()
        );
    }

    public function testInvalidConfiguration(): void
    {
        $reason = 'config service must return an array';
        $exception = ConfigurationException::invalidConfiguration($reason);

        $this->assertSame(
            'EdgeBinder configuration is invalid: config service must return an array',
            $exception->getMessage()
        );
    }

    public function testInstanceNotConfigured(): void
    {
        $instanceName = 'rag';
        $exception = ConfigurationException::instanceNotConfigured($instanceName);

        $this->assertSame(
            'EdgeBinder instance "rag" is not configured. Please add configuration for this instance.',
            $exception->getMessage()
        );
    }

    public function testMissingAdapter(): void
    {
        $exception = ConfigurationException::missingAdapter();

        $this->assertSame(
            'Adapter type is required in configuration. Please specify the "adapter" key in your EdgeBinder configuration.',
            $exception->getMessage()
        );
    }

    public function testUnsupportedAdapter(): void
    {
        $adapterType = 'unsupported';
        $exception = ConfigurationException::unsupportedAdapter($adapterType);

        $this->assertSame(
            'Unsupported adapter type "unsupported". Please register the adapter factory with AdapterRegistry.',
            $exception->getMessage()
        );
    }

    public function testMissingService(): void
    {
        $serviceName = 'weaviate.client.default';
        $exception = ConfigurationException::missingService($serviceName);

        $this->assertSame(
            'Required service "weaviate.client.default" is not registered in the container.',
            $exception->getMessage()
        );
    }

    public function testInvalidService(): void
    {
        $serviceName = 'weaviate.client.default';
        $reason = 'must return WeaviateClient instance';
        $exception = ConfigurationException::invalidService($serviceName, $reason);

        $this->assertSame(
            'Service "weaviate.client.default" is invalid: must return WeaviateClient instance',
            $exception->getMessage()
        );
    }

    public function testMissingRequiredKeys(): void
    {
        $missingKeys = ['adapter', 'client'];
        $context = 'adapter configuration';
        $exception = ConfigurationException::missingRequiredKeys($missingKeys, $context);

        $this->assertSame(
            'Missing required keys in adapter configuration: adapter, client',
            $exception->getMessage()
        );
    }

    public function testMissingRequiredKeysWithDefaultContext(): void
    {
        $missingKeys = ['host', 'port'];
        $exception = ConfigurationException::missingRequiredKeys($missingKeys);

        $this->assertSame(
            'Missing required keys in configuration: host, port',
            $exception->getMessage()
        );
    }

    public function testInvalidConfigurationValue(): void
    {
        $key = 'port';
        $value = 'invalid';
        $expectedType = 'integer';
        $exception = ConfigurationException::invalidConfigurationValue($key, $value, $expectedType);

        $this->assertSame(
            'Invalid value for configuration key "port": expected integer, got string',
            $exception->getMessage()
        );
    }

    public function testInvalidConfigurationValueWithComplexType(): void
    {
        $key = 'config';
        $value = new \stdClass();
        $expectedType = 'array or null';
        $exception = ConfigurationException::invalidConfigurationValue($key, $value, $expectedType);

        $this->assertSame(
            'Invalid value for configuration key "config": expected array or null, got stdClass',
            $exception->getMessage()
        );
    }

    public function testInvalidConfigurationValueWithNullValue(): void
    {
        $key = 'required_field';
        $value = null;
        $expectedType = 'string';
        $exception = ConfigurationException::invalidConfigurationValue($key, $value, $expectedType);

        $this->assertSame(
            'Invalid value for configuration key "required_field": expected string, got null',
            $exception->getMessage()
        );
    }

    public function testInvalidConfigurationValueWithArrayValue(): void
    {
        $key = 'simple_value';
        $value = ['complex', 'array'];
        $expectedType = 'string';
        $exception = ConfigurationException::invalidConfigurationValue($key, $value, $expectedType);

        $this->assertSame(
            'Invalid value for configuration key "simple_value": expected string, got array',
            $exception->getMessage()
        );
    }

    public function testFactoryMethodsReturnConfigurationExceptionInstances(): void
    {
        $exceptions = [
            ConfigurationException::missingConfiguration('test'),
            ConfigurationException::invalidConfiguration('test'),
            ConfigurationException::instanceNotConfigured('test'),
            ConfigurationException::unsupportedAdapter('test'),
            ConfigurationException::missingService('test'),
            ConfigurationException::invalidService('test', 'reason'),
            ConfigurationException::missingRequiredKeys(['key']),
            ConfigurationException::invalidConfigurationValue('key', 'value', 'type'),
        ];

        foreach ($exceptions as $exception) {
            $this->assertInstanceOf(ConfigurationException::class, $exception);
            $this->assertNotEmpty($exception->getMessage());
        }
    }
}

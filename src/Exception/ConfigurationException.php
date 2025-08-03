<?php

declare(strict_types=1);

namespace EdgeBinder\Component\Exception;

use InvalidArgumentException;

/**
 * Exception thrown when there are configuration-related errors in the EdgeBinder component.
 *
 * This exception provides factory methods for common configuration error scenarios
 * with helpful error messages and context information.
 */
final class ConfigurationException extends InvalidArgumentException
{
    /**
     * Create exception for missing configuration.
     *
     * @param string $reason Reason why configuration is missing
     *
     * @return self
     */
    public static function missingConfiguration(string $reason): self
    {
        return new self(sprintf('EdgeBinder configuration is missing: %s', $reason));
    }

    /**
     * Create exception for invalid configuration.
     *
     * @param string $reason Reason why configuration is invalid
     *
     * @return self
     */
    public static function invalidConfiguration(string $reason): self
    {
        return new self(sprintf('EdgeBinder configuration is invalid: %s', $reason));
    }

    /**
     * Create exception for unconfigured instance.
     *
     * @param string $instanceName Name of the instance that is not configured
     *
     * @return self
     */
    public static function instanceNotConfigured(string $instanceName): self
    {
        return new self(sprintf(
            'EdgeBinder instance "%s" is not configured. Please add configuration for this instance.',
            $instanceName
        ));
    }

    /**
     * Create exception for missing adapter configuration.
     *
     * @return self
     */
    public static function missingAdapter(): self
    {
        return new self(
            'Adapter type is required in configuration. Please specify the "adapter" key in your EdgeBinder configuration.'
        );
    }

    /**
     * Create exception for unsupported adapter type.
     *
     * @param string $adapterType The unsupported adapter type
     *
     * @return self
     */
    public static function unsupportedAdapter(string $adapterType): self
    {
        return new self(sprintf(
            'Unsupported adapter type "%s". Please register the adapter factory with AdapterRegistry.',
            $adapterType
        ));
    }

    /**
     * Create exception for missing service in container.
     *
     * @param string $serviceName Name of the missing service
     *
     * @return self
     */
    public static function missingService(string $serviceName): self
    {
        return new self(sprintf(
            'Required service "%s" is not registered in the container.',
            $serviceName
        ));
    }

    /**
     * Create exception for invalid service type.
     *
     * @param string $serviceName Name of the service
     * @param string $reason Reason why the service is invalid
     *
     * @return self
     */
    public static function invalidService(string $serviceName, string $reason): self
    {
        return new self(sprintf(
            'Service "%s" is invalid: %s',
            $serviceName,
            $reason
        ));
    }

    /**
     * Create exception for missing required configuration keys.
     *
     * @param array<string> $missingKeys Array of missing configuration keys
     * @param string $context Context where the keys are missing (e.g., 'adapter configuration')
     *
     * @return self
     */
    public static function missingRequiredKeys(array $missingKeys, string $context = 'configuration'): self
    {
        return new self(sprintf(
            'Missing required keys in %s: %s',
            $context,
            implode(', ', $missingKeys)
        ));
    }

    /**
     * Create exception for invalid configuration value.
     *
     * @param string $key Configuration key
     * @param mixed $value Invalid value
     * @param string $expectedType Expected type or description
     *
     * @return self
     */
    public static function invalidConfigurationValue(string $key, mixed $value, string $expectedType): self
    {
        return new self(sprintf(
            'Invalid value for configuration key "%s": expected %s, got %s',
            $key,
            $expectedType,
            get_debug_type($value)
        ));
    }
}

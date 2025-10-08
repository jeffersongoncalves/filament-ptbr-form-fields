<?php

namespace Leandrocfe\FilamentPtbrFormFields;

use Filament\Forms\Components\TextInput;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;
use Leandrocfe\FilamentPtbrFormFields\Concerns\HasCepModes;
use Leandrocfe\FilamentPtbrFormFields\Providers\CepProviderInterface;
use Leandrocfe\FilamentPtbrFormFields\Providers\ViaCepProvider;

/**
 * Brazilian ZIP code (CEP) form field component.
 *
 * Enables automatic address lookup through CEP providers (ViaCep, BrasilAPI, etc).
 */
class Cep extends TextInput
{
    use HasCepModes;

    protected null|string|Htmlable $defaultErrorMessage = 'CEP inválido';

    /**
     * Initial field configuration.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->required()
            ->minLength(9)
            ->mask('99999-999')
            ->placeholder('00000-000');
    }

    public function defaultErrorMessage(null|string|Htmlable $message): static
    {
        $this->defaultErrorMessage = $message ?? $this->defaultErrorMessage;
        return $this;
    }

    public function getDefaultErrorMessage(): null|string|Htmlable
    {
        return $this->defaultErrorMessage;
    }

    /**
     * Configure the API provider for CEP lookup.
     *
     * @param  string|CepProviderInterface  $provider  Provider class name or instance
     * @param  callable  $callback  Callback function that receives ($set, $response) to populate fields
     *
     * @throws InvalidArgumentException If the provider doesn't implement CepProviderInterface
     */
    public function api(string|CepProviderInterface $provider, callable $callback): static
    {
        $providerInstance = $this->resolveProvider($provider);

        $this->configureFieldMode($providerInstance, $callback);

        return $this;
    }

    /**
     * Resolve the provider to a valid instance.
     *
     * @throws InvalidArgumentException If the provider doesn't implement CepProviderInterface
     */
    private function resolveProvider(string|CepProviderInterface $provider): CepProviderInterface
    {
        $providerInstance = is_string($provider) ? new $provider : $provider;

        if (! $providerInstance instanceof CepProviderInterface) {
            throw new InvalidArgumentException(
                'The provider must implement the CepProviderInterface interface.'
            );
        }

        return $providerInstance;
    }

    /**
     * Legacy method for backward compatibility.
     *
     * @deprecated Use api() method instead.
     */
    public function viaCep(string $mode = 'suffix', string $errorMessage = 'CEP inválido.', array $setFields = []): static
    {
        return $this->api(
            provider: ViaCepProvider::class,
            callback: function ($set, $response) use ($setFields) {
                foreach ($setFields as $key => $value) {
                    $set($key, $response[$value] ?? null);
                }
            },
        );
    }
}

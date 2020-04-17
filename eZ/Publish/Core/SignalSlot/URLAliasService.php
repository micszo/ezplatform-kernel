<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace eZ\Publish\Core\SignalSlot;

use eZ\Publish\API\Repository\URLAliasService as URLAliasServiceInterface;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\SignalSlot\Signal\LocationService\UpdateLocationSignal;
use eZ\Publish\Core\SignalSlot\Signal\URLAliasService\CreateUrlAliasSignal;
use eZ\Publish\Core\SignalSlot\Signal\URLAliasService\CreateGlobalUrlAliasSignal;
use eZ\Publish\Core\SignalSlot\Signal\URLAliasService\RemoveAliasesSignal;

/**
 * URLAliasService class.
 */
class URLAliasService implements URLAliasServiceInterface
{
    /**
     * Aggregated service.
     *
     * @var \eZ\Publish\API\Repository\URLAliasService
     */
    protected $service;

    /**
     * SignalDispatcher.
     *
     * @var \eZ\Publish\Core\SignalSlot\SignalDispatcher
     */
    protected $signalDispatcher;

    /**
     * Constructor.
     *
     * Construct service object from aggregated service and signal
     * dispatcher
     *
     * @param \eZ\Publish\API\Repository\URLAliasService $service
     * @param \eZ\Publish\Core\SignalSlot\SignalDispatcher $signalDispatcher
     */
    public function __construct(URLAliasServiceInterface $service, SignalDispatcher $signalDispatcher)
    {
        $this->service = $service;
        $this->signalDispatcher = $signalDispatcher;
    }

    /**
     * Create a user chosen $alias pointing to $location in $languageCode.
     *
     * This method runs URL filters and transformers before storing them.
     * Hence the path returned in the URLAlias Value may differ from the given.
     * $alwaysAvailable makes the alias available in all languages.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param string $path
     * @param string $languageCode the languageCode for which this alias is valid
     * @param bool $forwarding if true a redirect is performed
     * @param bool $alwaysAvailable
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if the path already exists for the given language
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias
     */
    public function createUrlAlias(Location $location, $path, $languageCode, $forwarding = false, $alwaysAvailable = false)
    {
        $returnValue = $this->service->createUrlAlias($location, $path, $languageCode, $forwarding, $alwaysAvailable);
        $this->signalDispatcher->emit(
            new CreateUrlAliasSignal(
                [
                    'urlAliasId' => $returnValue->id,
                ]
            )
        );

        return $returnValue;
    }

    /**
     * Create a user chosen $alias pointing to a resource in $languageCode.
     *
     * This method does not handle location resources - if a user enters a location target
     * the createCustomUrlAlias method has to be used.
     * This method runs URL filters and and transformers before storing them.
     * Hence the path returned in the URLAlias Value may differ from the given.
     *
     * $alwaysAvailable makes the alias available in all languages.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if the path already exists for the given
     *         language or if resource is not valid
     *
     * @param string $resource
     * @param string $path
     * @param string $languageCode
     * @param bool $forwarding
     * @param bool $alwaysAvailable
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias
     */
    public function createGlobalUrlAlias($resource, $path, $languageCode, $forwarding = false, $alwaysAvailable = false)
    {
        $returnValue = $this->service->createGlobalUrlAlias($resource, $path, $languageCode, $forwarding, $alwaysAvailable);
        $this->signalDispatcher->emit(
            new CreateGlobalUrlAliasSignal(
                [
                    'urlAliasId' => $returnValue->id,
                ]
            )
        );

        return $returnValue;
    }

    /**
     * List of url aliases pointing to $location, sorted by language priority.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param bool $custom if true the user generated aliases are listed otherwise the autogenerated
     * @param string $languageCode filters those which are valid for the given language
     * @param bool|null $showAllTranslations Default false from config, include all alias as if they where always available.
     * @param array|null $prioritizedLanguageList By default taken from config, used as prioritized language codes for order of returned objects.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias[]
     */
    public function listLocationAliases(
        Location $location,
        $custom = true,
        $languageCode = null,
        bool $showAllTranslations = null,
        array $prioritizedLanguageList = null
    ) {
        return $this->service->listLocationAliases(
            $location,
            $custom,
            $languageCode,
            $showAllTranslations,
            $prioritizedLanguageList
        );
    }

    /**
     * List global aliases.
     *
     * @param string $languageCode filters those which are valid for the given language
     * @param int $offset
     * @param int $limit
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias[]
     */
    public function listGlobalAliases($languageCode = null, $offset = 0, $limit = -1)
    {
        return $this->service->listGlobalAliases($languageCode, $offset, $limit);
    }

    /**
     * Removes urls aliases.
     *
     * This method does not remove autogenerated aliases for locations.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if alias list contains
     *         autogenerated alias
     *
     * @param \eZ\Publish\API\Repository\Values\Content\URLAlias[] $aliasList
     */
    public function removeAliases(array $aliasList)
    {
        $returnValue = $this->service->removeAliases($aliasList);
        $this->signalDispatcher->emit(
            new RemoveAliasesSignal(
                [
                    'aliasList' => $aliasList,
                ]
            )
        );

        return $returnValue;
    }

    /**
     * looks up the URLAlias for the given url.
     *
     * @param string $url
     * @param string $languageCode
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the path does not exist or is not valid for the given language
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias
     */
    public function lookup($url, $languageCode = null)
    {
        return $this->service->lookup($url, $languageCode);
    }

    /**
     * Returns the URL alias for the given location in the given language.
     *
     * If $languageCode is null the method returns the url alias in the most prioritized language.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if no url alias exist for the given language
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param string $languageCode
     * @param bool|null $showAllTranslations Default false from config, include all alias as if they where always available.
     * @param array|null $prioritizedLanguageList By default taken from config, used as prioritized language codes for order of returned objects.
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias
     */
    public function reverseLookup(
        Location $location,
        $languageCode = null,
        bool $showAllTranslations = null,
        array $prioritizedLanguageList = null
    ) {
        return $this->service->reverseLookup(
            $location,
            $languageCode,
            $showAllTranslations,
            $prioritizedLanguageList
        );
    }

    /**
     * Loads URL alias by given $id.
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     *
     * @param string $id
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias
     */
    public function load($id)
    {
        return $this->service->load($id);
    }

    /**
     * Refresh all system URL aliases for the given Location (and historize outdated if needed).
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     */
    public function refreshSystemUrlAliasesForLocation(Location $location): void
    {
        $this->service->refreshSystemUrlAliasesForLocation($location);

        $this->signalDispatcher->emit(
            new UpdateLocationSignal(
                [
                    'contentId' => $location->contentId,
                    'locationId' => $location->id,
                    'parentLocationId' => $location->parentLocationId,
                ]
            )
        );
    }

    /**
     * Delete global, system or custom URL alias pointing to non-existent Locations.
     *
     * @return int Number of deleted URL aliases
     */
    public function deleteCorruptedUrlAliases(): int
    {
        return $this->service->deleteCorruptedUrlAliases();
    }
}

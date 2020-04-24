<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace eZ\Publish\Core\Repository\Validator;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\Values\ValueObject;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\Core\FieldType\FieldTypeRegistry;
use eZ\Publish\Core\FieldType\ValidationError;
use eZ\Publish\Core\Repository\Values\Content\VersionInfo;
use eZ\Publish\SPI\Repository\Validator\ContentValidator;

/**
 * @internal Meant for internal use by Repository
 */
final class VersionValidator implements ContentValidator
{
    /** @var \eZ\Publish\Core\FieldType\FieldTypeRegistry */
    private $fieldTypeRegistry;

    /** @var \eZ\Publish\API\Repository\ContentService */
    private $contentService;

    public function __construct(
        ContentService $contentService,
        FieldTypeRegistry $fieldTypeRegistry
    ) {
        $this->fieldTypeRegistry = $fieldTypeRegistry;
        $this->contentService = $contentService;
    }

    public function supports(ValueObject $object): bool
    {
        return $object instanceof VersionInfo;
    }

    public function validate(
        ValueObject $object,
        array $context = [],
        ?array $fieldIdentifiers = null
    ): array {
        if (!$this->supports($object)) {
            throw new InvalidArgumentException('$object', 'Not supported');
        }

        /** @var VersionInfo $versionInfo */
        $versionInfo = $object;

        $content = $this->contentService->loadContentByVersionInfo($versionInfo);

        $contentType = $content->getContentType();
        $languageCodes = $content->versionInfo->languageCodes;
        $mainLanguageCode = $content->contentInfo->mainLanguageCode;

        $allFieldErrors = [];

        foreach ($contentType->getFieldDefinitions() as $fieldDefinition) {
            if (isset($fieldIdentifiers) && !in_array($fieldDefinition->fieldTypeIdentifier, $fieldIdentifiers)) {
                continue;
            }

            $fieldType = $this->fieldTypeRegistry->getFieldType(
                $fieldDefinition->fieldTypeIdentifier
            );

            foreach ($languageCodes as $languageCode) {
                $valueLanguageCode = $fieldDefinition->isTranslatable ? $languageCode : $mainLanguageCode;
                $fieldValue = isset($fields[$fieldDefinition->identifier][$valueLanguageCode])
                    ? $fields[$fieldDefinition->identifier][$valueLanguageCode]->value
                    : $fieldDefinition->defaultValue;

                $fieldValue = $fieldType->acceptValue($fieldValue);

                if ($fieldType->isEmptyValue($fieldValue)) {
                    if ($fieldDefinition->isRequired) {
                        $allFieldErrors[$fieldDefinition->id][$languageCode] = new ValidationError(
                            "Value for required field definition '%identifier%' with language '%languageCode%' is empty",
                            null,
                            ['%identifier%' => $fieldDefinition->identifier, '%languageCode%' => $languageCode],
                            'empty'
                        );
                    }
                } else {
                    $fieldErrors = $fieldType->validate(
                        $fieldDefinition,
                        $fieldValue
                    );
                    if (!empty($fieldErrors)) {
                        $allFieldErrors[$fieldDefinition->id][$languageCode] = $fieldErrors;
                    }
                }
            }
        }

        return $allFieldErrors;
    }
}

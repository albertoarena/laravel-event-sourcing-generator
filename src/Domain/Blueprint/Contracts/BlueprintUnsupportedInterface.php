<?php

namespace Albertoarena\LaravelEventSourcingGenerator\Domain\Blueprint\Contracts;

interface BlueprintUnsupportedInterface
{
    public const SKIPPED_METHODS = [
        // Indexes
        'unique',
        'fullText',
        'spatialIndex',
        // Foreign keys
        'foreign',
        'cascadeOnUpdate',
        'restrictOnUpdate',
        'nullOnUpdate',
        'noActionOnUpdate',
        'cascadeOnDelete',
        'restrictOnDelete',
        'nullOnDelete',
        'noActionOnDelete',
        // Indexes
        'index',
        'rawIndex',
        'spatialIndex',
        // Soft deletes
        'softDeletes',
        'softDeletesTz',
        'softDeletesDatetime',
    ];

    public const UNSUPPORTED_COLUMN_TYPES = [
        'binary',
        'foreignIdFor',
        'foreignUlid',
        'geography',
        'geometry',
        'morphs',
        'nullableMorphs',
        'nullableUlidMorphs',
        'nullableUuidMorphs',
        'set',
        'ulidMorphs',
        'uuidMorphs',
        'ulid',
    ];

    public const IGNORED = [...self::SKIPPED_METHODS, ...self::UNSUPPORTED_COLUMN_TYPES];
}

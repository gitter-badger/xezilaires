<?php

declare(strict_types=1);

/*
 * This file is part of the xezilaires project.
 *
 * (c) Dalibor Karlović <dalibor@flexolabs.io>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Xezilaires\Metadata;

use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Xezilaires\Exception\HeaderException;
use Xezilaires\Exception\OptionsException;

/**
 * Class Mapping.
 */
class Mapping
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var array<string, Reference>
     */
    private $columns = [];

    /**
     * @var array<string, null|string|bool>
     */
    private $options;

    /**
     * @var ReferenceResolver
     */
    private $referenceResolver;

    /**
     * @var bool
     */
    private $headerOptionRequired = false;

    /**
     * @param string                   $className
     * @param array<string, Reference> $columns
     * @param array                    $options
     */
    public function __construct(string $className, array $columns, array $options = null)
    {
        $this->className = $className;
        $this->setColumns($columns);

        try {
            $resolver = new OptionsResolver();
            $this->configureOptions($resolver);

            /** @var array<string, null|string|bool> $options */
            $options = $resolver->resolve($options ?? []);
        } catch (OptionsResolverException $exception) {
            throw OptionsException::invalidOption($exception);
        }
        $this->setOptions($options);
    }

    /**
     * @param ReferenceResolver $referenceResolver
     */
    public function setReferenceResolver(ReferenceResolver $referenceResolver): void
    {
        $this->referenceResolver = $referenceResolver;
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return array<string, string>
     */
    public function getColumnMapping(): array
    {
        $mapping = [];
        foreach ($this->columns as $name => $column) {
            $mapping[$name] = $this->referenceResolver->resolve($column);
        }

        return $mapping;
    }

    /**
     * @param string $string
     *
     * @return null|string|bool
     */
    public function getOption(string $string)
    {
        return $this->options[$string];
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'start' => 1,
            'end' => null,
            'header' => null,
            'reverse' => false,
        ]);

        $resolver->setAllowedTypes('start', 'int');
        $resolver->setAllowedTypes('end', ['int', 'null']);
        $resolver->setAllowedTypes('header', ['int', 'null']);
        $resolver->setAllowedTypes('reverse', 'bool');
    }

    /**
     * @param array<string, Reference> $columns
     */
    private function setColumns(array $columns): void
    {
        $headerOptionRequired = false;
        foreach ($columns as $name => $column) {
            if (true === $column instanceof HeaderReference) {
                $headerOptionRequired = true;
            }

            $this->columns[$name] = $column;
        }

        $this->headerOptionRequired = $headerOptionRequired;
    }

    /**
     * @param array<string, null|string|bool> $options
     */
    private function setOptions(array $options): void
    {
        if (true === $this->headerOptionRequired && false === isset($options['header'])) {
            throw HeaderException::missingHeaderOption();
        }

        $this->options = $options;
    }
}
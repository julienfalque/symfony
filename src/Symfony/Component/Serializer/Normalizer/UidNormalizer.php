<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Normalizer;

use Symfony\Component\Serializer\DenormalizationResult;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\InvariantViolation;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\Uuid;

final class UidNormalizer implements NormalizerInterface, DenormalizerInterface, CacheableSupportsMethodInterface
{
    public const NORMALIZATION_FORMAT_KEY = 'uid_normalization_format';

    public const NORMALIZATION_FORMAT_CANONICAL = 'canonical';
    public const NORMALIZATION_FORMAT_BASE_58 = 'base_58';
    public const NORMALIZATION_FORMAT_BASE_32 = 'base_32';
    public const NORMALIZATION_FORMAT_RFC_4122 = 'rfc_4122';

    private $defaultContext = [
        self::NORMALIZATION_FORMAT_KEY => self::NORMALIZATION_FORMAT_CANONICAL,
    ];

    public function __construct(array $defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     *
     * @param AbstractUid $object
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        switch ($context[self::NORMALIZATION_FORMAT_KEY] ?? $this->defaultContext[self::NORMALIZATION_FORMAT_KEY]) {
            case self::NORMALIZATION_FORMAT_CANONICAL:
                return (string) $object;
            case self::NORMALIZATION_FORMAT_BASE_58:
                return $object->toBase58();
            case self::NORMALIZATION_FORMAT_BASE_32:
                return $object->toBase32();
            case self::NORMALIZATION_FORMAT_RFC_4122:
                return $object->toRfc4122();
        }

        throw new LogicException(sprintf('The "%s" format is not valid.', $context[self::NORMALIZATION_FORMAT_KEY] ?? $this->defaultContext[self::NORMALIZATION_FORMAT_KEY]));
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof AbstractUid;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        try {
            $result = Ulid::class === $type ? Ulid::fromString($data) : Uuid::fromString($data);
        } catch (\InvalidArgumentException $exception) {
            $message = sprintf('The data is not a valid "%s" string representation.', $type);

            if ($context[self::COLLECT_INVARIANT_VIOLATIONS] ?? false) {
                $violation = new InvariantViolation($data, $message, $exception);

                return DenormalizationResult::failure(['' => [$violation]]);
            }

            throw new NotNormalizableValueException($message);
        }

        if ($context[self::COLLECT_INVARIANT_VIOLATIONS] ?? false) {
            return DenormalizationResult::success($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return is_a($type, AbstractUid::class, true);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return __CLASS__ === static::class;
    }
}

<?php

namespace Arrilot\Api\Skeleton;

use League\Fractal\Resource\ResourceInterface;
use League\Fractal\Serializer\JsonApiSerializer;

class Serializer extends JsonApiSerializer
{
    /**
     * Serialize the included data
     *
     * @param \League\Fractal\Resource\ResourceInterface $resource
     * @param array $data
     * @return array
     */
    public function includedData(ResourceInterface $resource, array $data)
    {
        $serializedData = parent::includedData($resource, $data);

        return isset($serializedData['linked']) ? $serializedData['linked'] : [];
    }
}
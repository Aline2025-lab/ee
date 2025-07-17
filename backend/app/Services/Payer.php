<?php
namespace App\Services;

class Payer
{
    public string $partyIdType;
    public string $partyId;

    public function __construct(string $partyIdType, string $partyId)
    {
        $this->partyIdType = $partyIdType;
        $this->partyId = $partyId;
    }

    /**
     * Converts the Payer object to an associative array, suitable for JSON encoding.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'partyIdType' => $this->partyIdType,
            'partyId' => $this->partyId,
        ];
    }
}

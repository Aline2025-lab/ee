<?php

namespace App\Services;
/**
 * Represents a MoMo Transaction request body.
 */
class MoMoTransaction
{
    public string $amount;
    public string $currency;
    public string $externalId;
    public Payer $payer;
    public string $payerMessage;
    public string $payeeNote;

    public function __construct(
        string $amount,
        string $currency,
        string $externalId,
        Payer $payer,
        string $payerMessage,
        string $payeeNote
    ) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->externalId = $externalId;
        $this->payer = $payer;
        $this->payerMessage = $payerMessage;
        $this->payeeNote = $payeeNote;
    }

    /**
     * Converts the MoMoTransaction object to an associative array,
     * suitable for JSON encoding for the API request body.
     * @return array
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'externalId' => $this->externalId,
            'payer' => $this->payer->toArray(), // Convert the Payer object to its array representation
            'payerMessage' => $this->payerMessage,
            'payeeNote' => $this->payeeNote,
        ];
    }

    /**
     * Converts the MoMoTransaction object to a JSON string.
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}

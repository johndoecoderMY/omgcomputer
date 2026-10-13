<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\InventoryPart;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryStock
{
    public function receive(InventoryPart $part, User $user, int $quantity, ?string $note = null, ?string $reference = null): void
    {
        $this->move($part, $user, 'received', $quantity, $note, $reference);
    }

    public function use(InventoryPart $part, User $user, int $quantity, ?string $note = null, ?string $reference = null): void
    {
        $this->move($part, $user, 'used', -$quantity, $note, $reference);
    }

    public function setCount(InventoryPart $part, User $user, int $count, ?string $note = null): void
    {
        DB::transaction(function () use ($part, $user, $count, $note): void {
            $lockedPart = InventoryPart::query()->lockForUpdate()->findOrFail($part->id);
            $change = $count - $lockedPart->stock_quantity;

            if ($change !== 0) {
                $this->recordChange($lockedPart, $user, 'counted', $change, $note, null);
            }
        });
    }

    public function useForSale(InventoryPart $part, User $user, int $quantity, string $reference): void
    {
        $this->use($part, $user, $quantity, 'Deducted for issued invoice.', $reference);
    }

    private function move(
        InventoryPart $part,
        User $user,
        string $type,
        int $change,
        ?string $note,
        ?string $reference
    ): void {
        DB::transaction(function () use ($part, $user, $type, $change, $note, $reference): void {
            $lockedPart = InventoryPart::query()->lockForUpdate()->findOrFail($part->id);
            $this->recordChange($lockedPart, $user, $type, $change, $note, $reference);
        });
    }

    private function recordChange(
        InventoryPart $part,
        User $user,
        string $type,
        int $change,
        ?string $note,
        ?string $reference
    ): void {
        $newQuantity = $part->stock_quantity + $change;

        if ($newQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => "Not enough stock for {$part->name}. Available: {$part->stock_quantity}.",
            ]);
        }

        $part->update(['stock_quantity' => $newQuantity]);

        InventoryMovement::create([
            'inventory_part_id' => $part->id,
            'user_id' => $user->id,
            'type' => $type,
            'quantity_change' => $change,
            'quantity_after' => $newQuantity,
            'reference' => $reference,
            'note' => $note,
        ]);
    }
}

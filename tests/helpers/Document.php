<?php
namespace test\orm\helpers;

use DateTime;
use expresscore\orm\Collection;
use expresscore\orm\LazyCollection;
use JetBrains\PhpStorm\Pure;

class Document
{
    private ?int $id = null;
    private ?string $name = null;
    private User $FK_Usr_createdBy;
    private DateTime $createdAt;
    private Collection|LazyCollection $positions;
    private Collection|LazyCollection $warehouseDocuments;

    #[Pure] public function __construct()
    {
        $this->positions = new Collection(DocumentPosition::class);
        $this->warehouseDocuments = new Collection(WarehouseDocument::class);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getFK_Usr_createdBy(): User
    {
        return $this->FK_Usr_createdBy;
    }

    public function setFK_Usr_createdBy(User $FK_Usr_createdBy): void
    {
        $this->FK_Usr_createdBy = $FK_Usr_createdBy;
    }

    public function getWarehouseDocuments(): LazyCollection|Collection
    {
        return $this->warehouseDocuments;
    }

    public function getPositions(): LazyCollection|Collection
    {
        return $this->positions;
    }

    public function addPosition(DocumentPosition $documentPositon)
    {
        $this->positions->add($documentPositon);
    }

    public function addWarehouseDocument(WarehouseDocument $warehouseDocument)
    {
        $this->warehouseDocuments->add($warehouseDocument);
    }
}

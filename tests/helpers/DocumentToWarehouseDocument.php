<?php
namespace test\orm\helpers;

class DocumentToWarehouseDocument
{
    public ?int $id = null;
    public Document $FK_Doc_document;
    public WarehouseDocument $FK_WaD_warehouseDocument;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getFK_WaD_warehouseDocument(): WarehouseDocument
    {
        return $this->FK_WaD_warehouseDocument;
    }

    public function setFK_WaD_warehouseDocument(WarehouseDocument $FK_WaD_warehouseDocument): void
    {
        $this->FK_WaD_warehouseDocument = $FK_WaD_warehouseDocument;
    }

    public function getFK_Doc_document(): Document
    {
        return $this->FK_Doc_document;
    }

    public function setFK_Doc_document(Document $FK_Doc_document): void
    {
        $this->FK_Doc_document = $FK_Doc_document;
    }

}

<?php
namespace test\orm\helpers;

class DocumentPosition
{
    private ?int $id = null;
    private ?string $name = null;
    private Document $FK_Doc_document;

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
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

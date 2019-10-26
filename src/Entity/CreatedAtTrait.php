<?php
/**
 * This file is part of a Lyssal project.
 *
 * @copyright Rémi Leclerc
 * @author Rémi Leclerc
 */
namespace Lyssal\Doctrine\Orm\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * To add a createdAt property in an entity.
 *
 * Do not forget to add the `HasLifecycleCallbacks` annotation in your entity.
 *
 * @deprecated Please find this functionality in the Lyssal entity bundle lyssal/entity-bundle
 *
 * @ORM\HasLifecycleCallbacks()
 */
trait CreatedAtTrait
{
    /**
     * The creation date
     *
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    protected $createdAt;


    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAt(DateTimeInterface $createdAt)
    {
        $this->createdAt = $createdAt;
    }


    /**
     * Init the creation date.
     *
     * @ORM\PrePersist()
     */
    public function initCreatedAt()
    {
        $this->createdAt = new DateTime();
    }
}

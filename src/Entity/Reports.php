<?php

namespace App\Entity;

use App\Repository\ReportsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Reports
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    private ?User $user = null;
    
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\Column(length: 255)]
    private ?string $firm_logo_url = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $introduction = null;

    #[ORM\Column(length: 255)]
    private ?string $project_name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $purpose = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $test_methodology = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $test_environment = null;

    /**
     * @var Collection<int, Test>
     */
    #[ORM\OneToMany(targetEntity: Test::class, mappedBy: 'report')]
    private Collection $tests;

    /**
     * @var Collection<int, ReportVersion>
     */
    #[ORM\OneToMany(targetEntity: ReportVersion::class, mappedBy: 'report')]
    private Collection $reportVersions;

    public function __construct()
    {
        $this->tests = new ArrayCollection();
        $this->reportVersions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?user $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getFirmLogoUrl(): ?string
    {
        return $this->firm_logo_url;
    }

    public function setFirmLogoUrl(string $firm_logo_url): static
    {
        $this->firm_logo_url = $firm_logo_url;

        return $this;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(string $introduction): static
    {
        $this->introduction = $introduction;

        return $this;
    }

    public function getProjectName(): ?string
    {
        return $this->project_name;
    }

    public function setProjectName(string $project_name): static
    {
        $this->project_name = $project_name;

        return $this;
    }

    public function getPurpose(): ?string
    {
        return $this->purpose;
    }

    public function setPurpose(string $purpose): static
    {
        $this->purpose = $purpose;

        return $this;
    }

    public function getTestMethodology(): ?string
    {
        return $this->test_methodology;
    }

    public function setTestMethodology(string $test_methodology): static
    {
        $this->test_methodology = $test_methodology;

        return $this;
    }

    public function getTestEnvironment(): ?string
    {
        return $this->test_environment;
    }

    public function setTestEnvironment(string $test_environment): static
    {
        $this->test_environment = $test_environment;

        return $this;
    }

    /**
     * @return Collection<int, Test>
     */
    public function getTests(): Collection
    {
        return $this->tests;
    }

    public function addTest(Test $test): static
    {
        if (!$this->tests->contains($test)) {
            $this->tests->add($test);
            $test->setReport($this);
        }

        return $this;
    }

    public function removeTest(Test $test): static
    {
        if ($this->tests->removeElement($test)) {
            // set the owning side to null (unless already changed)
            if ($test->getReport() === $this) {
                $test->setReport(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ReportVersion>
     */
    public function getReportVersions(): Collection
    {
        return $this->reportVersions;
    }

    public function addReportVersion(ReportVersion $reportVersion): static
    {
        if (!$this->reportVersions->contains($reportVersion)) {
            $this->reportVersions->add($reportVersion);
            $reportVersion->setReport($this);
        }

        return $this;
    }

    public function removeReportVersion(ReportVersion $reportVersion): static
    {
        if ($this->reportVersions->removeElement($reportVersion)) {
            // set the owning side to null (unless already changed)
            if ($reportVersion->getReport() === $this) {
                $reportVersion->setReport(null);
            }
        }

        return $this;
    }
}
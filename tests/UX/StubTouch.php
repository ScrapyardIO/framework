<?php

namespace DeptOfScrapyardRobotics\Tests\UX;

use Fabricate\Contracts\Actuation\HumanInput\CoordinateSpace;
use Fabricate\Contracts\Actuation\HumanInput\Touch;
use Fabricate\Contracts\Actuation\HumanInput\TouchContact;
use Fabricate\Contracts\Actuation\HumanInput\TouchPhase;

/**
 * A touch panel reporting whatever contacts a test hands it.
 *
 * Contacts are stored in the space they were created in and converted on the
 * way out, so a test can pin the normalised-to-pixel mapping the router relies
 * on against a panel that only speaks fractions — which is what a resistive
 * touchscreen actually does.
 */
final class StubTouch implements Touch
{
    /**
     * @var array<int, TouchContact>
     */
    protected array $contacts = [];

    public int $polls = 0;

    /**
     * @param  bool  $honours_request  false for a panel that only ever speaks
     *                                 fractions, which is what the router's own
     *                                 conversion exists for
     */
    public function __construct(
        protected int $width = 0,
        protected int $height = 0,
        protected bool $honours_request = true,
    ) {}

    public function contact(float $x, float $y, TouchPhase $phase = TouchPhase::BEGAN, CoordinateSpace $space = CoordinateSpace::PIXELS): static
    {
        $this->contacts[] = new TouchContact(count($this->contacts), $x, $y, $phase, $space);

        return $this;
    }

    public function clear(): static
    {
        $this->contacts = [];

        return $this;
    }

    public function poll(): static
    {
        $this->polls++;

        return $this;
    }

    /**
     * @return list<TouchContact>
     */
    public function contacts(CoordinateSpace $space = CoordinateSpace::NORMALIZED): array
    {
        return array_map(
            fn (TouchContact $contact): TouchContact => $this->convert($contact, $space),
            $this->contacts,
        );
    }

    public function primaryContact(CoordinateSpace $space = CoordinateSpace::NORMALIZED): ?TouchContact
    {
        return $this->contacts($space)[0] ?? null;
    }

    public function close(): void
    {
        //
    }

    protected function convert(TouchContact $contact, CoordinateSpace $space): TouchContact
    {
        if (($contact->space === $space) || ! $this->honours_request) {
            return $contact;
        }

        [$x, $y] = ($space === CoordinateSpace::PIXELS)
            ? [$contact->x * $this->width, $contact->y * $this->height]
            : [$contact->x / max(1, $this->width), $contact->y / max(1, $this->height)];

        return new TouchContact($contact->id, $x, $y, $contact->phase, $space, $contact->pressure);
    }
}

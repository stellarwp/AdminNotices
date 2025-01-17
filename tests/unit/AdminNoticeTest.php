<?php

declare(strict_types=1);

namespace StellarWP\AdminNotices\Tests\Unit;

use DateTimeImmutable;
use InvalidArgumentException;
use StellarWP\AdminNotices\AdminNotice;
use StellarWP\AdminNotices\Tests\Support\Helper\TestCase;
use StellarWP\AdminNotices\ValueObjects\NoticeLocation;
use StellarWP\AdminNotices\ValueObjects\NoticeUrgency;
use StellarWP\AdminNotices\ValueObjects\ScreenCondition;
use StellarWP\AdminNotices\ValueObjects\UserCapability;

/**
 * @coversDefaultClass \StellarWP\AdminNotices\AdminNotice
 */
class AdminNoticeTest extends TestCase
{
    /**
     * @covers ::__construct
     *
     * @since 1.0.0
     */
    public function testThrowsExceptionWhenRenderIsNotStringOrCallable()
    {
        $this->expectException(InvalidArgumentException::class);
        new AdminNotice('test', 1);
    }

    /**
     * @covers ::ifUserCan
     *
     * @since 1.0.0
     */
    public function testIfUserCan(): void
    {
        $notice = new AdminNotice('test_id', 'test');
        $self = $notice->ifUserCan('test', ['test', 1], ['test', 2, 3]);

        $this->assertCount(3, $notice->getUserCapabilities());
        $this->assertContainsOnlyInstancesOf(UserCapability::class, $notice->getUserCapabilities());
        $this->assertEquals(
            [new UserCapability('test'), new UserCapability('test', [1]), new UserCapability('test', [2, 3])],
            $notice->getUserCapabilities()
        );
        $this->assertSame($notice, $self);
    }

    /**
     * @covers ::ifUserCan
     *
     * @since 1.0.0
     */
    public function testIfUserCanShouldThrowExceptionWhenCapabilityIsNotStringOrArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $notice = new AdminNotice('test_id', 'test');
        $notice->ifUserCan(1);
    }

    /**
     * @covers ::ifUserCan
     *
     * @since 1.0.0
     */
    public function testIfUserCanShouldThrowExceptionWhenCapabilityArrayIsMisshaped(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $notice = new AdminNotice('test_id', 'test');
        $notice->ifUserCan([]);
    }

    /**
     * @covers ::after
     * @covers ::parseDate
     * @covers ::getAfterDate
     *
     * @dataProvider dateTestProvider
     *
     * @since 1.0.0
     */
    public function testAfter($parameter, $assertDate): void
    {
        $notice = new AdminNotice('test_id', 'test');
        $self = $notice->after($parameter);
        $this->assertInstanceOf(DateTimeImmutable::class, $notice->getAfterDate());
        $this->assertSame($assertDate, $notice->getAfterDate()->format('Y-m-d'));
        $this->assertSame($notice, $self);
    }

    /**
     * @covers ::until
     * @covers ::parseDate
     * @covers ::getUntilDate
     *
     * @dataProvider dateTestProvider
     *
     * @since 1.0.0
     */
    public function testUntil($parameter, $assertDate): void
    {
        $notice = new AdminNotice('test_id', 'test');
        $self = $notice->until($parameter);
        $this->assertInstanceOf(DateTimeImmutable::class, $notice->getUntilDate());
        $this->assertSame($assertDate, $notice->getUntilDate()->format('Y-m-d'));
        $this->assertSame($notice, $self);
    }

    /**
     * @covers ::between
     *
     * @since 1.0.0
     */
    public function testBetween(): void
    {
        $notice = new AdminNotice('test_id', 'test');
        $self = $notice->between('2021-01-01', '2021-02-01');
        $this->assertInstanceOf(DateTimeImmutable::class, $notice->getAfterDate());
        $this->assertSame('2021-01-01', $notice->getAfterDate()->format('Y-m-d'));
        $this->assertInstanceOf(DateTimeImmutable::class, $notice->getUntilDate());
        $this->assertSame('2021-02-01', $notice->getUntilDate()->format('Y-m-d'));
        $this->assertSame($notice, $self);
    }

    public function dateTestProvider(): array
    {
        return [
            ['2021-01-01', '2021-01-01'], // accepts a string
            [1612137600, '2021-02-01'], // accepts a UNIX timestamp
            [new DateTimeImmutable('2021-01-01'), '2021-01-01'], // accepts a DateTimeInterface object
        ];
    }

    /**
     * @covers ::when
     * @covers ::getWhenCallback
     *
     * @since 1.0.0
     */
    public function testWhen(): void
    {
        $notice = new AdminNotice('test_id', 'test');
        $self = $notice->when(function () {
            return true;
        });

        $this->assertTrue($notice->getWhenCallback()());
        $this->assertSame($notice, $self);
    }

    /**
     * @covers ::on
     * @covers ::getOnConditions
     *
     * @since 1.0.0
     */
    public function testOn(): void
    {
        $notice = new AdminNotice('test_id', 'test');
        $self = $notice->on('test', new ScreenCondition('test2'));

        $this->assertEquals([new ScreenCondition('test'), new ScreenCondition('test2')], $notice->getOnConditions());
        $this->assertSame($notice, $self);
    }

    /**
     * @covers ::autoParagraph
     * @covers ::withoutAutoParagraph
     * @covers ::shouldAutoParagraph
     *
     * @since 1.0.0
     */
    public function testAutoParagraph(): void
    {
        // Defaults to false
        $notice = new AdminNotice('test_id', 'test');
        $this->assertFalse($notice->shouldAutoParagraph());

        // Method defaults to true
        $self = $notice->autoParagraph();
        $this->assertTrue($notice->shouldAutoParagraph());
        $this->assertSame($notice, $self);

        // Method can be set to false
        $notice->autoParagraph(false);
        $this->assertFalse($notice->shouldAutoParagraph());

        // Method can be explicitly set to true
        $notice->autoParagraph(true);
        $this->assertTrue($notice->shouldAutoParagraph());

        // withoutAutoParagraph is an alias for autoParagraph(false)
        $self = $notice->withoutAutoParagraph();
        $this->assertFalse($notice->shouldAutoParagraph());
        $this->assertSame($notice, $self);
    }

    /**
     * @covers ::urgency
     * @covers ::getUrgency
     *
     * @since 1.0.0
     */
    public function testUrgency(): void
    {
        // Defaults to 'info'
        $notice = new AdminNotice('test_id', 'test');
        $this->assertEquals('info', $notice->getUrgency());

        // Can be set with string
        $self = $notice->urgency('error');
        $this->assertEquals('error', $notice->getUrgency());
        $this->assertSame($notice, $self);

        // Can be set with NoticeUrgency object
        $notice->urgency(new NoticeUrgency('warning'));
        $this->assertEquals('warning', $notice->getUrgency());
    }

    /**
     * @covers ::dismissible
     * @covers ::notDismissible
     * @covers ::isDismissible
     *
     * @since 1.0.0
     */
    public function testDismissible(): void
    {
        // Defaults to false
        $notice = new AdminNotice('test_id', 'test');
        $this->assertFalse($notice->isDismissible());

        // Method defaults to true
        $self = $notice->dismissible();
        $this->assertTrue($notice->isDismissible());
        $this->assertSame($notice, $self);

        // Method can be explicitly set to false
        $notice->dismissible(false);
        $this->assertFalse($notice->isDismissible());

        // Method can be set to true
        $notice->dismissible(true);
        $this->assertTrue($notice->isDismissible());

        // notDismissible is an alias for dismissible(false)
        $self = $notice->notDismissible();
        $this->assertFalse($notice->isDismissible());
        $this->assertSame($notice, $self);
    }

    /**
     * @covers ::getRenderTextOrCallback
     *
     * @since 1.0.0
     */
    public function testGetRenderTextOrCallback(): void
    {
        // Returns the render text
        $notice = new AdminNotice('test_id', 'test');
        $this->assertSame('test', $notice->getRenderTextOrCallback());

        // Returns the render callback
        $callback = function () {};
        $notice = new AdminNotice('test_id', $callback);
        $this->assertSame($callback, $notice->getRenderTextOrCallback());
    }

    /**
     * @covers ::getRenderedContent
     *
     * @since 1.0.0
     */
    public function testRenderedContent(): void
    {
        // Returns the plain, rendered text
        $notice = new AdminNotice('test_id', 'test');
        $this->assertSame('test', $notice->getRenderedContent());

        // Returns the text with auto-paragraphs
        $notice = (new AdminNotice('test_id', 'test'))
            ->autoParagraph();
        $this->assertSame(wpautop('test'), $notice->getRenderedContent());

        // Returns the results of the callback
        $notice = new AdminNotice('test_id', function () {
            return 'test-callback';
        });
        $this->assertSame('test-callback', $notice->getRenderedContent());
    }

    /**
     * @covers ::getUserCapabilities
     *
     * @since 1.0.0
     */
    public function testGetUserCapabilities(): void
    {
        // Defaults to empty array
        $notice = new AdminNotice('test_id', 'test');
        $this->assertEmpty($notice->getUserCapabilities());

        // Returns the user capabilities
        $notice->ifUserCan('test');
        $this->assertCount(1, $notice->getUserCapabilities());
        $this->assertContainsOnlyInstancesOf(UserCapability::class, $notice->getUserCapabilities());
        $this->assertEquals([new UserCapability('test')], $notice->getUserCapabilities());
    }

    /**
     * @covers ::getAfterDate
     *
     * @since 1.0.0
     */
    public function testGetAfterDate(): void
    {
        // Defaults to null
        $notice = new AdminNotice('test_id', 'test');
        $this->assertNull($notice->getAfterDate());

        // Returns the date after which the notice should be displayed
        $notice->after('2021-01-01');
        $this->assertInstanceOf(DateTimeImmutable::class, $notice->getAfterDate());
        $this->assertSame('2021-01-01', $notice->getAfterDate()->format('Y-m-d'));
    }

    /**
     * @covers ::getUntilDate
     *
     * @since 1.0.0
     */
    public function testGetUntilDate(): void
    {
        // Defaults to null
        $notice = new AdminNotice('test_id', 'test');
        $this->assertNull($notice->getUntilDate());

        // Returns the date until which the notice should be displayed
        $notice->until('2021-01-01');
        $this->assertInstanceOf(DateTimeImmutable::class, $notice->getUntilDate());
        $this->assertSame('2021-01-01', $notice->getUntilDate()->format('Y-m-d'));
    }

    /**
     * @covers ::getWhenCallback
     *
     * @since 1.0.0
     */
    public function testGetWhenCallback(): void
    {
        // Defaults to null
        $notice = new AdminNotice('test_id', 'test');
        $this->assertNull($notice->getWhenCallback());

        // Returns the callback
        $callback = function () {};
        $notice->when($callback);
        $this->assertSame($callback, $notice->getWhenCallback());
    }

    /**
     * @covers ::alternateStyles
     * @covers ::standardStyles
     * @covers ::usesAlternateStyles
     *
     * @since 1.2.0
     */
    public function testAlternateStyles(): void
    {
        $notice = new AdminNotice('test_id', 'test');

        // Defaults to false
        $this->assertFalse($notice->usesAlternateStyles());

        // Method can be set to true
        $self = $notice->alternateStyles();
        $this->assertTrue($notice->usesAlternateStyles());
        $this->assertSame($notice, $self);

        // Method can be explicitly set to false
        $notice->alternateStyles(false);
        $this->assertFalse($notice->usesAlternateStyles());

        // Method can be set to true
        $notice->alternateStyles(true);
        $this->assertTrue($notice->usesAlternateStyles());

        // standardStyles is an alias for alternateStyles(false)
        $self = $notice->standardStyles();
        $this->assertFalse($notice->usesAlternateStyles());
        $this->assertSame($notice, $self);
    }

    /**
     * @covers ::custom
     * @covers ::isCustom
     *
     * @since 2.0.0
     */
    public function testCustom(): void
    {
        $notice = new AdminNotice('test_id', 'test');

        // Defaults to false
        $this->assertFalse($notice->isCustom());

        // Method can be set to true
        $self = $notice->custom();
        $this->assertTrue($notice->isCustom());
        $this->assertSame($notice, $self);

        // Method can be explicitly set to false
        $notice->custom(false);
        $this->assertFalse($notice->isCustom());
    }

    /**
     * @covers ::location
     * @covers ::getLocation
     *
     * @since 2.0.0
     */
    public function testLocation(): void
    {
        $notice = new AdminNotice('test_id', 'test');

        // Defaults to standard
        $this->assertTrue($notice->getLocation()->isStandard());

        // Returns the location
        $self = $notice->location(NoticeLocation::inline());
        $this->assertTrue($notice->getLocation()->isInline());
        $this->assertSame($notice, $self);
    }
}

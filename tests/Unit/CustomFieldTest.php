<?php
namespace Tests\Unit;

use App\Models\CustomField;
use Tests\TestCase;

/*
 * Test strings for db column names  gathered from
 * http://www.omniglot.com/language/phrases/hovercraft.htm
 */
class CustomFieldTest extends TestCase
{
    public function testFormat()
    {
        $customfield = CustomField::factory()->make(['format' => 'IP']);
        $this->assertEquals($customfield->getAttributes()['format'], CustomField::PREDEFINED_FORMATS['IP']); //this seems undocumented...
        $this->assertEquals($customfield->format, 'IP');
    }

    public function testDbNameAscii()
    {
        $customfield = new CustomField();
        $customfield->name = 'My hovercraft is full of eels';
        $customfield->id = 1337;
        $this->assertEquals($customfield->convertUnicodeDbSlug(), '_snipeit_my_hovercraft_is_full_of_eels_1337');
    }

    // Western Europe
    public function testDbNameLatin()
    {
        $customfield = new CustomField();
        $customfield->name = 'My hovercraft is full of eels';
        $customfield->id = 1337;
        $this->assertEquals($customfield->convertUnicodeDbSlug(), '_snipeit_my_hovercraft_is_full_of_eels_1337');
    }

    // Asian
    public function testDbNameChinese()
    {
        $this->assertSafeUnicodeDbSlug('我的氣墊船裝滿了鱔魚');
    }

    public function testDbNameJapanese()
    {
        $this->assertSafeUnicodeDbSlug('私のホバークラフトは鰻でいっぱいです');
    }

    public function testDbNameKorean()
    {
        $this->assertSafeUnicodeDbSlug('내 호버크라프트는 장어로 가득 차 있어요');
    }

    // Nordic languages
    public function testDbNameNonLatinEuro()
    {
        $this->assertSafeUnicodeDbSlug('Mój poduszkowiec jest pełen węgorzy');
    }

    //
    public function testDbNameTurkish()
    {
        $this->assertSafeUnicodeDbSlug('Hoverkraftım yılan balığı dolu');
    }

    public function testDbNameArabic()
    {
        $this->assertSafeUnicodeDbSlug('حَوّامتي مُمْتِلئة بِأَنْقَلَيْسون');
    }

    private function assertSafeUnicodeDbSlug(string $name): void
    {
        $customfield = new CustomField();
        $customfield->name = $name;
        $customfield->id = 1337;

        $slug = $customfield->convertUnicodeDbSlug();

        $this->assertSame($slug, $customfield->convertUnicodeDbSlug());
        $this->assertMatchesRegularExpression('/\A_snipeit_[a-z0-9_]+_1337\z/', $slug);
        $this->assertLessThanOrEqual(55, strlen($slug));
    }
}

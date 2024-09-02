<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230902165227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `category` CHANGE `hotline_link` `hotline_category` VARCHAR(255) DEFAULT NULL;');
        $this->addSql('DROP INDEX IDX_64C19C17B00651C ON category');
        $this->addSql('DROP INDEX IDX_64C19C12B36786B ON category');
        $this->addSql('CREATE INDEX IDX_64C19C17B00651C ON category (status)');
        $this->addSql('CREATE INDEX IDX_64C19C12B36786B ON category (title)');
        $this->addSql('CREATE INDEX IDX_9474526CE7927C74 ON comment (email)');
        $this->addSql('CREATE INDEX IDX_9474526C8B8E8428 ON comment (created_at)');
        $this->addSql('CREATE INDEX IDX_6956883F77153098 ON currency (code)');
        $this->addSql('CREATE INDEX IDX_5D429FB32B36786B ON delivery_type (title)');
        $this->addSql('CREATE INDEX IDX_D2294458E7927C74 ON feedback (email)');
        $this->addSql('CREATE INDEX IDX_D22944588B8E8428 ON feedback (created_at)');
        $this->addSql('DROP INDEX IDX_EFF6EB791D775834 ON filter_attributes');
        $this->addSql('CREATE INDEX IDX_EFF6EB791D775834 ON filter_attributes (value)');
        $this->addSql('DROP INDEX IDX_7877678D2B36786B ON filters');
        $this->addSql('CREATE INDEX IDX_7877678D2B36786B ON filters (title)');
        $this->addSql('CREATE INDEX IDX_15D46CB72B36786B ON nova_poshta_city (title)');
        $this->addSql('CREATE INDEX IDX_15D46CB7146F3EA3 ON nova_poshta_city (ref)');
        $this->addSql('CREATE INDEX IDX_15D46CB78B8E8428 ON nova_poshta_city (created_at)');
        $this->addSql('CREATE INDEX IDX_CEF5D45A2B36786B ON nova_poshta_office (title)');
        $this->addSql('CREATE INDEX IDX_CEF5D45A146F3EA3 ON nova_poshta_office (ref)');
        $this->addSql('CREATE INDEX IDX_CEF5D45A8B8E8428 ON nova_poshta_office (created_at)');
        $this->addSql('CREATE INDEX IDX_E52FFDEE551F0F81 ON orders (order_number)');
        $this->addSql('CREATE INDEX IDX_E52FFDEEE7769B0F ON orders (surname)');
        $this->addSql('CREATE INDEX IDX_E52FFDEE444F97DD ON orders (phone)');
        $this->addSql('CREATE INDEX IDX_E52FFDEEE7927C74 ON orders (email)');
        $this->addSql('CREATE INDEX IDX_E52FFDEE7B00651C ON orders (status)');
        $this->addSql('CREATE INDEX IDX_E52FFDEE8B8E8428 ON orders (created_at)');
        $this->addSql('CREATE INDEX IDX_AD5DC05D2B36786B ON payment_type (title)');
        $this->addSql('DROP INDEX IDX_D34A04AD7B00651C ON product');
        $this->addSql('DROP INDEX IDX_D34A04AD2B36786B ON product');
        $this->addSql('CREATE INDEX IDX_D34A04AD7B00651C ON product (status)');
        $this->addSql('CREATE INDEX IDX_D34A04AD2B36786B ON product (title)');
        $this->addSql('CREATE INDEX IDX_9F74B8982B36786B ON setting (title)');
        $this->addSql('ALTER TABLE supplier_order_product DROP FOREIGN KEY FK_37ED2D214584665A');
        $this->addSql('ALTER TABLE supplier_order_product ADD CONSTRAINT FK_37ED2D214584665A FOREIGN KEY (product_id) REFERENCES supplier_product (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_8D93D649E7927C74 ON user (email)');
        $this->addSql('CREATE INDEX IDX_8D93D649E7769B0F ON user (surname)');
        $this->addSql('CREATE INDEX IDX_8D93D649444F97DD ON user (phone)');
        $this->addSql('ALTER TABLE category ADD prom_category_link VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `category` CHANGE `hotline_category` `hotline_link` VARCHAR(255) DEFAULT NULL;');
        $this->addSql('DROP INDEX IDX_64C19C12B36786B ON category');
        $this->addSql('DROP INDEX IDX_64C19C17B00651C ON category');
        $this->addSql('CREATE INDEX IDX_64C19C12B36786B ON category (title(191))');
        $this->addSql('CREATE INDEX IDX_64C19C17B00651C ON category (status(191))');
        $this->addSql('DROP INDEX IDX_9474526CE7927C74 ON comment');
        $this->addSql('DROP INDEX IDX_9474526C8B8E8428 ON comment');
        $this->addSql('DROP INDEX IDX_6956883F77153098 ON currency');
        $this->addSql('DROP INDEX IDX_5D429FB32B36786B ON delivery_type');
        $this->addSql('DROP INDEX IDX_D2294458E7927C74 ON feedback');
        $this->addSql('DROP INDEX IDX_D22944588B8E8428 ON feedback');
        $this->addSql('DROP INDEX IDX_EFF6EB791D775834 ON filter_attributes');
        $this->addSql('CREATE INDEX IDX_EFF6EB791D775834 ON filter_attributes (value(191))');
        $this->addSql('DROP INDEX IDX_7877678D2B36786B ON filters');
        $this->addSql('CREATE INDEX IDX_7877678D2B36786B ON filters (title(191))');
        $this->addSql('DROP INDEX IDX_15D46CB72B36786B ON nova_poshta_city');
        $this->addSql('DROP INDEX IDX_15D46CB7146F3EA3 ON nova_poshta_city');
        $this->addSql('DROP INDEX IDX_15D46CB78B8E8428 ON nova_poshta_city');
        $this->addSql('DROP INDEX IDX_CEF5D45A2B36786B ON nova_poshta_office');
        $this->addSql('DROP INDEX IDX_CEF5D45A146F3EA3 ON nova_poshta_office');
        $this->addSql('DROP INDEX IDX_CEF5D45A8B8E8428 ON nova_poshta_office');
        $this->addSql('DROP INDEX IDX_E52FFDEE551F0F81 ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEEE7769B0F ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEE444F97DD ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEEE7927C74 ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEE7B00651C ON orders');
        $this->addSql('DROP INDEX IDX_E52FFDEE8B8E8428 ON orders');
        $this->addSql('DROP INDEX IDX_AD5DC05D2B36786B ON payment_type');
        $this->addSql('DROP INDEX IDX_D34A04AD7B00651C ON product');
        $this->addSql('DROP INDEX IDX_D34A04AD2B36786B ON product');
        $this->addSql('CREATE INDEX IDX_D34A04AD7B00651C ON product (status(191))');
        $this->addSql('CREATE INDEX IDX_D34A04AD2B36786B ON product (title(191))');
        $this->addSql('DROP INDEX IDX_9F74B8982B36786B ON setting');
        $this->addSql('ALTER TABLE supplier_order_product DROP FOREIGN KEY FK_37ED2D214584665A');
        $this->addSql('ALTER TABLE supplier_order_product ADD CONSTRAINT FK_37ED2D214584665A FOREIGN KEY (product_id) REFERENCES product (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('DROP INDEX IDX_8D93D649E7927C74 ON user');
        $this->addSql('DROP INDEX IDX_8D93D649E7769B0F ON user');
        $this->addSql('DROP INDEX IDX_8D93D649444F97DD ON user');
        $this->addSql('ALTER TABLE category DROP prom_category_link');
    }
}

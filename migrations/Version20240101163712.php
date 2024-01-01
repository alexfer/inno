<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240101163712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE answer_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE attach_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE contact_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE entry_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE entry_attachment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE entry_category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE entry_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE faq_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_brand_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_category_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_category_product_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_invoice_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_manufacturer_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_orders_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_orders_product_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_payment_gateway_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_product_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_product_attach_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_product_brand_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_product_manufacturer_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_product_supplier_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE market_supplier_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE reset_password_request_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_details_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE user_social_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE answer (id INT NOT NULL, contact_id INT NOT NULL, user_id INT NOT NULL, message TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DADD4A25E7A1254A ON answer (contact_id)');
        $this->addSql('CREATE INDEX IDX_DADD4A25A76ED395 ON answer (user_id)');
        $this->addSql('CREATE TABLE attach (id INT NOT NULL, user_details_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, mime VARCHAR(255) NOT NULL, size INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_DEC97C811C7DC1CE ON attach (user_details_id)');
        $this->addSql('CREATE TABLE category (id INT NOT NULL, slug VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(512) DEFAULT NULL, position SMALLINT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C1989D9B62 ON category (slug)');
        $this->addSql('CREATE TABLE contact (id INT NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, answers INT NOT NULL, phone VARCHAR(255) DEFAULT NULL, subject VARCHAR(255) DEFAULT NULL, message TEXT NOT NULL, email VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE entry (id INT NOT NULL, user_id INT DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, comments INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2B219D70989D9B62 ON entry (slug)');
        $this->addSql('CREATE INDEX IDX_2B219D70A76ED395 ON entry (user_id)');
        $this->addSql('CREATE INDEX idx ON entry (status, type)');
        $this->addSql('CREATE TABLE entry_attachment (id INT NOT NULL, attach_id INT DEFAULT NULL, details_id INT DEFAULT NULL, in_use INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E1F59089E784F8B7 ON entry_attachment (attach_id)');
        $this->addSql('CREATE INDEX IDX_E1F59089BB1A0722 ON entry_attachment (details_id)');
        $this->addSql('CREATE TABLE entry_category (id INT NOT NULL, entry_id INT DEFAULT NULL, category_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_680BF989BA364942 ON entry_category (entry_id)');
        $this->addSql('CREATE INDEX IDX_680BF98912469DE2 ON entry_category (category_id)');
        $this->addSql('CREATE TABLE entry_details (id INT NOT NULL, entry_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, content TEXT DEFAULT NULL, short_content TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5EC0A41DBA364942 ON entry_details (entry_id)');
        $this->addSql('CREATE TABLE faq (id INT NOT NULL, title VARCHAR(255) NOT NULL, content TEXT NOT NULL, visible INT DEFAULT 0 NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE market (id INT NOT NULL, owner_id INT DEFAULT NULL, attach_id INT DEFAULT NULL, name VARCHAR(512) NOT NULL, address VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL, slug VARCHAR(512) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, currency VARCHAR(5) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6BAC85CB989D9B62 ON market (slug)');
        $this->addSql('CREATE INDEX IDX_6BAC85CB7E3C61F9 ON market (owner_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6BAC85CBE784F8B7 ON market (attach_id)');
        $this->addSql('CREATE TABLE market_brand (id INT NOT NULL, market_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, url VARCHAR(512) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D77C462D622F3F37 ON market_brand (market_id)');
        $this->addSql('CREATE TABLE market_category (id INT NOT NULL, name VARCHAR(512) NOT NULL, description TEXT DEFAULT NULL, slug VARCHAR(512) DEFAULT NULL, position INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_EBFD0C09989D9B62 ON market_category (slug)');
        $this->addSql('COMMENT ON COLUMN market_category.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE market_category_product (id INT NOT NULL, product_id INT DEFAULT NULL, category_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_89E568864584665A ON market_category_product (product_id)');
        $this->addSql('CREATE INDEX IDX_89E5688612469DE2 ON market_category_product (category_id)');
        $this->addSql('CREATE TABLE market_invoice (id INT NOT NULL, orders_id INT DEFAULT NULL, payment_gateway_id INT DEFAULT NULL, number VARCHAR(50) NOT NULL, tax DOUBLE PRECISION NOT NULL, amount DOUBLE PRECISION NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, paid_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_99F3FFC4CFFE9AD6 ON market_invoice (orders_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_99F3FFC462890FD5 ON market_invoice (payment_gateway_id)');
        $this->addSql('COMMENT ON COLUMN market_invoice.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE market_manufacturer (id INT NOT NULL, market_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_B77B4092622F3F37 ON market_manufacturer (market_id)');
        $this->addSql('CREATE TABLE market_orders (id INT NOT NULL, market_id INT DEFAULT NULL, number VARCHAR(50) NOT NULL, total DOUBLE PRECISION NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C58B56E2622F3F37 ON market_orders (market_id)');
        $this->addSql('COMMENT ON COLUMN market_orders.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE market_orders_product (id INT NOT NULL, orders_id INT DEFAULT NULL, product_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_737098F1CFFE9AD6 ON market_orders_product (orders_id)');
        $this->addSql('CREATE INDEX IDX_737098F14584665A ON market_orders_product (product_id)');
        $this->addSql('CREATE TABLE market_payment_gateway (id INT NOT NULL, name VARCHAR(100) NOT NULL, summary TEXT NOT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE market_product (id INT NOT NULL, market_id INT DEFAULT NULL, name VARCHAR(512) NOT NULL, description TEXT NOT NULL, slug VARCHAR(512) DEFAULT NULL, quantity INT NOT NULL, cost DOUBLE PRECISION NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DADCEC2D989D9B62 ON market_product (slug)');
        $this->addSql('CREATE INDEX IDX_DADCEC2D622F3F37 ON market_product (market_id)');
        $this->addSql('CREATE TABLE market_product_attach (id INT NOT NULL, product_id INT DEFAULT NULL, attach_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EDFAD3024584665A ON market_product_attach (product_id)');
        $this->addSql('CREATE INDEX IDX_EDFAD302E784F8B7 ON market_product_attach (attach_id)');
        $this->addSql('CREATE TABLE market_product_brand (id INT NOT NULL, product_id INT DEFAULT NULL, brand_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2CE09F354584665A ON market_product_brand (product_id)');
        $this->addSql('CREATE INDEX IDX_2CE09F3544F5D008 ON market_product_brand (brand_id)');
        $this->addSql('CREATE TABLE market_product_manufacturer (id INT NOT NULL, product_id INT DEFAULT NULL, manufacturer_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_93EA846B4584665A ON market_product_manufacturer (product_id)');
        $this->addSql('CREATE INDEX IDX_93EA846BA23B42D ON market_product_manufacturer (manufacturer_id)');
        $this->addSql('CREATE TABLE market_product_supplier (id INT NOT NULL, product_id INT DEFAULT NULL, supplier_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CDED2ABC4584665A ON market_product_supplier (product_id)');
        $this->addSql('CREATE INDEX IDX_CDED2ABC2ADD6D8C ON market_product_supplier (supplier_id)');
        $this->addSql('CREATE TABLE market_supplier (id INT NOT NULL, market_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, country VARCHAR(3) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_769B79B6622F3F37 ON market_supplier (market_id)');
        $this->addSql('CREATE TABLE reset_password_request (id INT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7CE748AA76ED395 ON reset_password_request (user_id)');
        $this->addSql('COMMENT ON COLUMN reset_password_request.requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, attach_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, ip VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E784F8B7 ON "user" (attach_id)');
        $this->addSql('CREATE TABLE user_details (id INT NOT NULL, user_id INT DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, country VARCHAR(2) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, about TEXT DEFAULT NULL, date_birth TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2A2B1580A76ED395 ON user_details (user_id)');
        $this->addSql('CREATE TABLE user_social (id INT NOT NULL, details_id INT DEFAULT NULL, facebook_profile VARCHAR(512) DEFAULT NULL, twitter_profile VARCHAR(512) DEFAULT NULL, instagram_profile VARCHAR(512) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1433FABABB1A0722 ON user_social (details_id)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('CREATE TABLE rememberme_token (series VARCHAR(88) NOT NULL, value VARCHAR(88) NOT NULL, lastUsed TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, class VARCHAR(100) NOT NULL, username VARCHAR(200) NOT NULL, PRIMARY KEY(series))');
        $this->addSql('COMMENT ON COLUMN rememberme_token.lastUsed IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A25E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A25A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE attach ADD CONSTRAINT FK_DEC97C811C7DC1CE FOREIGN KEY (user_details_id) REFERENCES user_details (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entry ADD CONSTRAINT FK_2B219D70A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entry_attachment ADD CONSTRAINT FK_E1F59089E784F8B7 FOREIGN KEY (attach_id) REFERENCES attach (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entry_attachment ADD CONSTRAINT FK_E1F59089BB1A0722 FOREIGN KEY (details_id) REFERENCES entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entry_category ADD CONSTRAINT FK_680BF989BA364942 FOREIGN KEY (entry_id) REFERENCES entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entry_category ADD CONSTRAINT FK_680BF98912469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE entry_details ADD CONSTRAINT FK_5EC0A41DBA364942 FOREIGN KEY (entry_id) REFERENCES entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market ADD CONSTRAINT FK_6BAC85CB7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market ADD CONSTRAINT FK_6BAC85CBE784F8B7 FOREIGN KEY (attach_id) REFERENCES attach (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_brand ADD CONSTRAINT FK_D77C462D622F3F37 FOREIGN KEY (market_id) REFERENCES market (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_category_product ADD CONSTRAINT FK_89E568864584665A FOREIGN KEY (product_id) REFERENCES market_product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_category_product ADD CONSTRAINT FK_89E5688612469DE2 FOREIGN KEY (category_id) REFERENCES market_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_invoice ADD CONSTRAINT FK_99F3FFC4CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES market_orders (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_invoice ADD CONSTRAINT FK_99F3FFC462890FD5 FOREIGN KEY (payment_gateway_id) REFERENCES market_payment_gateway (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_manufacturer ADD CONSTRAINT FK_B77B4092622F3F37 FOREIGN KEY (market_id) REFERENCES market (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_orders ADD CONSTRAINT FK_C58B56E2622F3F37 FOREIGN KEY (market_id) REFERENCES market (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_orders_product ADD CONSTRAINT FK_737098F1CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES market_orders (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_orders_product ADD CONSTRAINT FK_737098F14584665A FOREIGN KEY (product_id) REFERENCES market_product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_product ADD CONSTRAINT FK_DADCEC2D622F3F37 FOREIGN KEY (market_id) REFERENCES market (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_product_attach ADD CONSTRAINT FK_EDFAD3024584665A FOREIGN KEY (product_id) REFERENCES market_product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_product_attach ADD CONSTRAINT FK_EDFAD302E784F8B7 FOREIGN KEY (attach_id) REFERENCES attach (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_product_brand ADD CONSTRAINT FK_2CE09F354584665A FOREIGN KEY (product_id) REFERENCES market_product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_product_brand ADD CONSTRAINT FK_2CE09F3544F5D008 FOREIGN KEY (brand_id) REFERENCES market_brand (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_product_manufacturer ADD CONSTRAINT FK_93EA846B4584665A FOREIGN KEY (product_id) REFERENCES market_product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_product_manufacturer ADD CONSTRAINT FK_93EA846BA23B42D FOREIGN KEY (manufacturer_id) REFERENCES market_manufacturer (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_product_supplier ADD CONSTRAINT FK_CDED2ABC4584665A FOREIGN KEY (product_id) REFERENCES market_product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_product_supplier ADD CONSTRAINT FK_CDED2ABC2ADD6D8C FOREIGN KEY (supplier_id) REFERENCES market_supplier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_supplier ADD CONSTRAINT FK_769B79B6622F3F37 FOREIGN KEY (market_id) REFERENCES market (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "user" ADD CONSTRAINT FK_8D93D649E784F8B7 FOREIGN KEY (attach_id) REFERENCES attach (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_details ADD CONSTRAINT FK_2A2B1580A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_social ADD CONSTRAINT FK_1433FABABB1A0722 FOREIGN KEY (details_id) REFERENCES user_details (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE answer_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE attach_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE category_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE contact_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE entry_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE entry_attachment_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE entry_category_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE entry_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE faq_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_brand_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_category_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_category_product_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_invoice_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_manufacturer_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_orders_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_orders_product_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_payment_gateway_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_product_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_product_attach_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_product_brand_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_product_manufacturer_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_product_supplier_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE market_supplier_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE reset_password_request_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE user_details_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE user_social_id_seq CASCADE');
        $this->addSql('ALTER TABLE answer DROP CONSTRAINT FK_DADD4A25E7A1254A');
        $this->addSql('ALTER TABLE answer DROP CONSTRAINT FK_DADD4A25A76ED395');
        $this->addSql('ALTER TABLE attach DROP CONSTRAINT FK_DEC97C811C7DC1CE');
        $this->addSql('ALTER TABLE entry DROP CONSTRAINT FK_2B219D70A76ED395');
        $this->addSql('ALTER TABLE entry_attachment DROP CONSTRAINT FK_E1F59089E784F8B7');
        $this->addSql('ALTER TABLE entry_attachment DROP CONSTRAINT FK_E1F59089BB1A0722');
        $this->addSql('ALTER TABLE entry_category DROP CONSTRAINT FK_680BF989BA364942');
        $this->addSql('ALTER TABLE entry_category DROP CONSTRAINT FK_680BF98912469DE2');
        $this->addSql('ALTER TABLE entry_details DROP CONSTRAINT FK_5EC0A41DBA364942');
        $this->addSql('ALTER TABLE market DROP CONSTRAINT FK_6BAC85CB7E3C61F9');
        $this->addSql('ALTER TABLE market DROP CONSTRAINT FK_6BAC85CBE784F8B7');
        $this->addSql('ALTER TABLE market_brand DROP CONSTRAINT FK_D77C462D622F3F37');
        $this->addSql('ALTER TABLE market_category_product DROP CONSTRAINT FK_89E568864584665A');
        $this->addSql('ALTER TABLE market_category_product DROP CONSTRAINT FK_89E5688612469DE2');
        $this->addSql('ALTER TABLE market_invoice DROP CONSTRAINT FK_99F3FFC4CFFE9AD6');
        $this->addSql('ALTER TABLE market_invoice DROP CONSTRAINT FK_99F3FFC462890FD5');
        $this->addSql('ALTER TABLE market_manufacturer DROP CONSTRAINT FK_B77B4092622F3F37');
        $this->addSql('ALTER TABLE market_orders DROP CONSTRAINT FK_C58B56E2622F3F37');
        $this->addSql('ALTER TABLE market_orders_product DROP CONSTRAINT FK_737098F1CFFE9AD6');
        $this->addSql('ALTER TABLE market_orders_product DROP CONSTRAINT FK_737098F14584665A');
        $this->addSql('ALTER TABLE market_product DROP CONSTRAINT FK_DADCEC2D622F3F37');
        $this->addSql('ALTER TABLE market_product_attach DROP CONSTRAINT FK_EDFAD3024584665A');
        $this->addSql('ALTER TABLE market_product_attach DROP CONSTRAINT FK_EDFAD302E784F8B7');
        $this->addSql('ALTER TABLE market_product_brand DROP CONSTRAINT FK_2CE09F354584665A');
        $this->addSql('ALTER TABLE market_product_brand DROP CONSTRAINT FK_2CE09F3544F5D008');
        $this->addSql('ALTER TABLE market_product_manufacturer DROP CONSTRAINT FK_93EA846B4584665A');
        $this->addSql('ALTER TABLE market_product_manufacturer DROP CONSTRAINT FK_93EA846BA23B42D');
        $this->addSql('ALTER TABLE market_product_supplier DROP CONSTRAINT FK_CDED2ABC4584665A');
        $this->addSql('ALTER TABLE market_product_supplier DROP CONSTRAINT FK_CDED2ABC2ADD6D8C');
        $this->addSql('ALTER TABLE market_supplier DROP CONSTRAINT FK_769B79B6622F3F37');
        $this->addSql('ALTER TABLE reset_password_request DROP CONSTRAINT FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT FK_8D93D649E784F8B7');
        $this->addSql('ALTER TABLE user_details DROP CONSTRAINT FK_2A2B1580A76ED395');
        $this->addSql('ALTER TABLE user_social DROP CONSTRAINT FK_1433FABABB1A0722');
        $this->addSql('DROP TABLE answer');
        $this->addSql('DROP TABLE attach');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE entry');
        $this->addSql('DROP TABLE entry_attachment');
        $this->addSql('DROP TABLE entry_category');
        $this->addSql('DROP TABLE entry_details');
        $this->addSql('DROP TABLE faq');
        $this->addSql('DROP TABLE market');
        $this->addSql('DROP TABLE market_brand');
        $this->addSql('DROP TABLE market_category');
        $this->addSql('DROP TABLE market_category_product');
        $this->addSql('DROP TABLE market_invoice');
        $this->addSql('DROP TABLE market_manufacturer');
        $this->addSql('DROP TABLE market_orders');
        $this->addSql('DROP TABLE market_orders_product');
        $this->addSql('DROP TABLE market_payment_gateway');
        $this->addSql('DROP TABLE market_product');
        $this->addSql('DROP TABLE market_product_attach');
        $this->addSql('DROP TABLE market_product_brand');
        $this->addSql('DROP TABLE market_product_manufacturer');
        $this->addSql('DROP TABLE market_product_supplier');
        $this->addSql('DROP TABLE market_supplier');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE user_details');
        $this->addSql('DROP TABLE user_social');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('DROP TABLE rememberme_token');
    }
}

FROM php:7.3-apache

RUN apt update -y

RUN apt install -y unzip wget \
; \
rm -rf /var/lib/apt/lists/*; \
\
docker-php-ext-install mysqli

EXPOSE 80
COPY entry.sh /entry.sh
RUN chmod +x /entry.sh
ENTRYPOINT ["/entry.sh"]

CMD ["apache2-foreground"]

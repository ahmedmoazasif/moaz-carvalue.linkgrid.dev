FROM almalinux:9

# No sudo needed in Docker (run as root)
RUN dnf install -y git

RUN dnf install -y nginx
COPY nginx/moaz-carvalue.conf /etc/nginx/conf.d/moaz-carvalue.conf
RUN systemctl enable nginx

RUN dnf install -y mariadb-server
RUN systemctl enable mariadb

RUN dnf install -y php php-fpm php-mysqlnd php-cli php-gd php-mbstring php-xml php-common
RUN systemctl enable php-fpm

# Clean dnf cache to reduce image size
RUN dnf clean all

# Run systemd so enabled services start when container runs.
# Use: docker run --privileged -d your-image
# Or with cgroups v2: docker run --privileged --cgroupns=host -d your-image
CMD ["/usr/sbin/init"]

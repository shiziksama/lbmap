FROM debian:bookworm AS builder

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        build-essential \
        pkg-config \
        libboost-dev \
        libosmium2-dev \
        nlohmann-json3-dev \
        zlib1g-dev \
        libbz2-dev \
        libexpat1-dev \
    && rm -rf /var/lib/apt/lists/*

COPY o5m/filter_osmium.cpp /src/filter_osmium.cpp

RUN mkdir -p /out \
    && g++ -O2 -std=c++17 /src/filter_osmium.cpp -o /out/filter_osmium_cpp \
        -lexpat -lz -lbz2

FROM debian:bookworm-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        osmium-tool \
        python3 \
        python3-pyosmium \
        ca-certificates \
    && rm -rf /var/lib/apt/lists/*

COPY --from=builder /out/filter_osmium_cpp /usr/local/bin/filter_osmium_cpp
COPY o5m/pbf_pipeline.sh /usr/local/bin/pbf_pipeline.sh

RUN chmod +x /usr/local/bin/pbf_pipeline.sh

WORKDIR /data

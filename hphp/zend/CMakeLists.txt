set(C_SOURCES)
set(CXX_SOURCES)
auto_sources(files "*.c" "RECURSE" "${CMAKE_CURRENT_SOURCE_DIR}")
list(APPEND C_SOURCES ${files})
auto_sources(files "*.cpp" "RECURSE" "${CMAKE_CURRENT_SOURCE_DIR}")
list(APPEND CXX_SOURCES ${files})

if (NOT MSVC)
  list(REMOVE_ITEM C_SOURCES
    ${CMAKE_CURRENT_SOURCE_DIR}/crypt-freesec.c
    ${CMAKE_CURRENT_SOURCE_DIR}/crypt-sha256.c
    ${CMAKE_CURRENT_SOURCE_DIR}/crypt-sha512.c
    ${CMAKE_CURRENT_SOURCE_DIR}/php-crypt_r.c
  )
endif()

add_library(hphp_zend STATIC ${C_SOURCES} ${CXX_SOURCES})

auto_sources(files "*.h" "RECURSE")
HHVM_PUBLIC_HEADERS(zend ${files})

set(CXX_SOURCES)
set(C_SOURCES)
set(ASM_SOURCES)
set(HEADER_SOURCES)
set(IDL_SOURCES)
set(IDL_DEFINES)
set(PHP_SOURCES)

# Pick up all files in the base ext/ folder unconditionally
auto_sources(files "*.cpp" "${CMAKE_CURRENT_SOURCE_DIR}")
list(APPEND CXX_SOURCES ${files})
auto_sources(files "*.c" "${CMAKE_CURRENT_SOURCE_DIR}")
list(APPEND C_SOURCES ${files})
auto_sources(files "*.S" "${CMAKE_CURRENT_SOURCE_DIR}")
list(APPEND ASM_SOURCES ${files})
auto_sources(files "*.h" "${CMAKE_CURRENT_SOURCE_DIR}")
list(APPEND HEADER_SOURCES ${files})

# Temporary while closure/generator are being worked on
HHVM_REMOVE_MATCHES_FROM_LISTS(CXX_SOURCES
  MATCHES "ext_(closure|generator)\\.cpp")

set(HRE_LIBARIES)
include(EXTFunctions)
include(HHVMExtensionConfig)

# For each directory under ext/, either pull in standard
# source lists, or let the particular extension make that
# decision on its own
file(GLOB subdirs ${CMAKE_CURRENT_SOURCE_DIR}/*)
foreach (dir ${subdirs})
  if (IS_DIRECTORY ${dir})
    if (NOT EXISTS "${dir}/config.cmake")
      HHVM_SELECT_SOURCES(${dir})
    endif()
    # Otherwise the sources are already known.
  endif()
endforeach()
HHVM_EXTENSION_BUILD_SOURCE_LISTS()
set(EXTENSION_IDL_SOURCES ${IDL_SOURCES} CACHE INTERNAL "" FORCE)
set(EXTENSION_SYSTEMLIB_SOURCES ${PHP_SOURCES} CACHE INTERNAL "" FORCE)

add_precompiled_header(
  "${HPHP_HOME}/hphp/pch/hphp_runtime_ext.h"
  "${HPHP_HOME}/hphp/pch/hphp_runtime_ext.cpp"
  CXX_SOURCES
)

ADD_LIBRARY(hphp_runtime_ext OBJECT
            ${CXX_SOURCES} ${C_SOURCES} ${ASM_SOURCES} ${HEADER_SOURCES})
auto_source_group("hphp_runtime_ext" "${CMAKE_CURRENT_SOURCE_DIR}"
  ${ASM_SOURCES} ${C_SOURCES} ${CXX_SOURCES} ${HEADER_SOURCES})
if (ENABLE_ASYNC_MYSQL)
  add_dependencies(hphp_runtime_ext webscalesqlclient squangle)
endif ()

HHVM_PUBLIC_HEADERS(ext ${HEADER_SOURCES})

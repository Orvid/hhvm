/*
   +----------------------------------------------------------------------+
   | HipHop for PHP                                                       |
   +----------------------------------------------------------------------+
   | Copyright (c) 2010-2015 Facebook, Inc. (http://www.facebook.com)     |
   +----------------------------------------------------------------------+
   | This source file is subject to version 3.01 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available through the world-wide-web at the following url:           |
   | http://www.php.net/license/3_01.txt                                  |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
*/

#ifndef incl_HPHP_STATIC_CONTENT_CACHE_H_
#define incl_HPHP_STATIC_CONTENT_CACHE_H_

#include <memory>

#include "hphp/runtime/base/string-buffer.h"
#include "hphp/util/file-cache.h"

namespace HPHP {
///////////////////////////////////////////////////////////////////////////////

class StaticContentCache {
public:
  static StaticContentCache TheCache;
  static std::shared_ptr<FileCache> TheFileCache;

public:
  /**
   * Load all registered static files from RuntimeOption::DocumentRoot.
   */
  void load();

  /**
   * Find a file from cache.
   */
  bool find(const std::string &name, const char *&data, int &len,
            bool &compressed) const;
};

///////////////////////////////////////////////////////////////////////////////
}

#endif // incl_HPHP_STATIC_CONTENT_CACHE_H_

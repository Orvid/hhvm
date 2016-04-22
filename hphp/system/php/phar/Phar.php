<?php
// This doc comment block generated by idl/sysdoc.php
/**
 * ( excerpt from http://php.net/manual/en/class.phar.php )
 *
 * The Phar class provides a high-level interface to accessing and
 * creating phar archives.
 *
 */
class Phar extends RecursiveDirectoryIterator
  implements Countable, ArrayAccess {

  const NONE = 0;
  const COMPRESSED = 0x0000F000;
  const GZ = 0x00001000;
  const BZ2 = 0x00002000;
  const SIGNATURE = 0x00010000;

  const SAME = 0;
  const PHAR = 1;
  const TAR = 2;
  const ZIP = 3;

  const MD5 = 0x0001;
  const SHA1 = 0x0002;
  const SHA256 = 0x0003;
  const SHA512 = 0x0004;
  const OPENSSL = 0x0010;

  const PHP = 1;
  const PHPS = 2;

  const HALT_TOKEN = '__HALT_COMPILER();';

  /**
   * A map from filename_or_alias => Phar object
   */
  private static $aliases = array();
  /**
   * Prevent the check for __HALT_COMPILER()
   */
  private static $preventHaltTokenCheck = false;
  /**
   * Prevent the check for file extension
   */
  private static $preventExtCheck = false;

  private $alias;
  private $fileInfo = array();
  private $fileOffsets = array();
  private $stub;
  private $manifest;
  private $contents;
  private $signature;
  /**
   * @var bool|int
   */
  private $compressed = false;

  private $count;
  private $apiVersion;
  private $archiveFlags;
  private $metadata;
  private $signatureFlags;

  private $iteratorRoot;
  private $iterator;

  private $fp;

  /**
   * ( excerpt from http://php.net/manual/en/phar.construct.php )
   *
   *
   * @param string $fname Path to an existing Phar archive or to-be-created
   *                      archive. The file name's extension must contain
   *                      .phar.
   * @param int $flags    Flags to pass to parent class
   *                      RecursiveDirectoryIterator.
   * @param string $alias Alias with which this Phar archive should be referred
   *                      to in calls to stream functionality.
   *
   * @throws PharException
   * @throws UnexpectedValueException
   */
  public function __construct($fname, $flags = null, $alias = null) {
    if (!self::$preventExtCheck && !self::isValidPharFilename($fname)) {
      throw new UnexpectedValueException(
        "Cannot create phar '$fname', file extension (or combination) not".
        ' recognised or the directory does not exist'
      );
    }
    if (!is_file($fname)) {
      throw new UnexpectedValueException("$fname is not a file");
    }
    $this->fp = fopen($fname, 'rb');

    $magic_number = fread($this->fp, 4);
    // This is not a bullet-proof check, but should be good enough to catch ZIP
    if (strcmp($magic_number, "PK\x03\x04") === 0) {
      $this->construct_zip($fname, $flags, $alias);
      return;
    }
    // Tar + BZ2
    if (strpos($magic_number, 'BZ') === 0) {
      $this->compressed = self::BZ2;
    }
    // Tar + GZ
    if (strpos($magic_number, "\x1F\x8B") === 0) {
      $this->compressed = self::GZ;
    }
    fseek($this->fp, 127);
    $magic_number = fread($this->fp, 8);
    // Compressed or just Tar
    if (
      $this->compressed ||
      strpos($magic_number, "ustar\x0") === 0 ||
      strpos($magic_number, "ustar\x40\x40\x0") === 0
    ) {
      $this->construct_tar($fname, $flags, $alias);
      return;
    }
    // Otherwise Phar
    $this->construct_phar($fname, $flags, $alias);
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.addemptydir.php )
   *
   *
   * @param string $dirname The name of the empty directory to create in the
   *                        phar archive
   * @param int $levels     The number of parent directories to go up. This
   *                        must be an integer greater than 0.
   *
   * @return void no return value, exception is thrown on failure.
   */
  public function addEmptyDir($dirname, $levels = 1) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.addfile.php )
   *
   *
   * @param string $file      Full or relative path to a file on disk to be
   *                          added to the phar archive.
   * @param string $localname Path that the file will be stored in the archive.
   *
   * @return void no return value, exception is thrown on failure.
   */
  public function addFile($file, $localname = null) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.addfromstring.php )
   *
   *
   * @param string $localname Path that the file will be stored in the archive.
   * @param string $contents  The file contents to store
   *
   * @return void no return value, exception is thrown on failure.
   */
  public function addFromString($localname, $contents) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.buildfromdirectory.php )
   *
   *
   * @param string $base_dir The full or relative path to the directory that
   *                         contains all files to add to the archive.
   * @param string $regex    An optional pcre regular expression that is used
   *                         to filter the list of files. Only file paths
   *                         matching the regular expression will be included
   *                         in the archive.
   *
   * @return array <b>Phar::buildFromDirectory</b> returns an associative array
   */
  public function buildFromDirectory($base_dir, $regex = null) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.buildfromiterator.php )
   *
   *
   * @param Iterator $iter         Any iterator that either associatively maps
   *                               phar file to location or returns SplFileInfo
   *                               objects
   * @param string $base_directory For iterators that return SplFileInfo
   *                               objects, the portion of each file's full
   *                               path to remove when adding to the phar
   *                               archive
   *
   * @return array <b>Phar::buildFromIterator</b> returns an associative array
   *                               mapping internal path of file to the full
   *                               path of the file on the filesystem.
   */
  public function buildFromIterator($iter, $base_directory = null) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.compressfiles.php )
   *
   *
   * @param int $compression Compression must be one of Phar::GZ,
   *                         Phar::BZ2 to add compression, or Phar::NONE
   *                         to remove compression.
   *
   * @return void No value is returned.
   */
  public function compressFiles($compression) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.decompressfiles.php )
   *
   *
   * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
   */
  public function decompressFiles() {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.compress.php )
   *
   *
   * @param int $compression  Compression must be one of Phar::GZ,
   *                          Phar::BZ2 to add compression, or Phar::NONE
   *                          to remove compression.
   * @param string $extension By default, the extension is .phar.gz
   *                          or .phar.bz2 for compressing phar archives, and
   *                          .phar.tar.gz or .phar.tar.bz2 for
   *                          compressing tar archives. For decompressing, the
   *                          default file extensions are .phar and .phar.tar.
   *
   * @return object a <b>Phar</b> object.
   */
  public function compress($compression, $extension = null) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.decompress.php )
   *
   *
   * @param string $extension For decompressing, the default file extensions
   *                          are .phar and .phar.tar.
   *                          Use this parameter to specify another file
   *                          extension. Be aware that all executable phar
   *                          archives must contain .phar in their filename.
   *
   * @return object A <b>Phar</b> object is returned.
   */
  public function decompress($extension = null) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.converttoexecutable.php )
   *
   *
   * @param int $format       This should be one of Phar::PHAR, Phar::TAR,
   *                          or Phar::ZIP. If set to <b>NULL</b>, the existing
   *                          file format will be preserved.
   * @param int $compression  This should be one of Phar::NONE for no
   *                          whole-archive compression, Phar::GZ for
   *                          zlib-based compression, and Phar::BZ2 for
   *                          bzip-based compression.
   * @param string $extension This parameter is used to override the default
   *                          file extension for a converted archive. Note that
   *                          all zip- and tar-based phar archives must contain
   *                          .phar in their file extension in order to be
   *                          processed as a phar archive.
   *
   *                          If converting to a phar-based archive, the
   *                          default extensions are
   *                          .phar, .phar.gz, or .phar.bz2
   *                          depending on the specified compression. For
   *                          tar-based phar archives, the default extensions
   *                          are .phar.tar, .phar.tar.gz, and .phar.tar.bz2.
   *                          For zip-based phar archives, the default
   *                          extension is .phar.zip.
   *
   * @return Phar The method returns a <b>Phar</b> object on success and throws
   *              an exception on failure.
   */
  public function convertToExecutable($format = 9021976,
                                      $compression_type = 9021976,
                                      $file_ext = null) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.converttodata.php )
   *
   * @param int $format       This should be one of Phar::TAR
   *                          or Phar::ZIP. If set to <b>NULL</b>, the existing
   *                          file format will be preserved.
   * @param int $compression  This should be one of Phar::NONE for no
   *                          whole-archive compression, Phar::GZ for
   *                          zlib-based compression, and Phar::BZ2 for
   *                          bzip-based compression.
   * @param string $extension This parameter is used to override the default
   *                          file extension for a converted archive. Note that
   *                          .phar cannot be used anywhere in the filename for
   *                          a non-executable tar or zip archive.
   *                          </p>
   *                          If converting to a tar-based phar archive, the
   *                          default extensions are .tar, .tar.gz,
   *                          and .tar.bz2 depending on specified compression.
   *                          For zip-based archives, the
   *                          default extension is .zip.
   *
   * @return PharData The method returns a <b>PharData</b> object on success
   *                  and throws an exception on failure.
   */
  public function convertToData($format = 9021976,
                                $compression_type = 9021976,
                                $extension = null) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.copy.php )
   *
   *
   * @param string $oldfile
   * @param string $newfile
   * @return bool returns <b>TRUE</b> on success, but it is safer to encase
   *              method call in a try/catch block and assume success if no
   *              exception is thrown.
   */
  public function copy($oldfile, $newfile) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.count.php )
   *
   *
   * @return int The number of files contained within this phar, or 0 (the
   *             number zero) if none.
   */
  public function count() {
    return $this->count;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.delete.php )
   *
   *
   * @param string $entry Path within an archive to the file to delete.
   *
   * @return bool returns <b>TRUE</b> on success, but it is better to check for
   *              thrown exception, and assume success if none is thrown.
   */
  public function delete($entry) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.delmetadata.php )
   *
   *
   * @return bool returns <b>TRUE</b> on success, but it is better to check for
   *              thrown exception, and assume success if none is thrown.
   */
  public function delMetadata() {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.extractto.php )
   *
   *
   * @param string $pathto      Path within an archive to the file to delete.
   * @param string|array $files The name of a file or directory to extract, or
   *                            an array of files/directories to extract
   * @param bool $overwrite     Set to <b>TRUE</b> to enable overwriting
   *                            existing files
   *
   * @return bool returns <b>TRUE</b> on success, but it is better to check for
   *              thrown exception, and assume success if none is thrown.
   */
  public function extractTo($pathto, $files = null, $overwrite = false) {
    throw new UnexpectedValueException('phar is read-only');
  }

  public function getAlias() {
    return $this->alias;
  }

  public function getPath() {
    return $this->path;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.getmetadata.php )
   *
   * @return mixed any PHP variable that can be serialized and is stored as
   *               meta-data for the Phar archive, or <b>NULL</b> if no
   *               meta-data is stored.
   */
  public function getMetadata() {
    return $this->metadata;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.getmodified.php )
   *
   * @return bool <b>TRUE</b> if the phar has been modified since opened,
   *              <b>FALSE</b> if not.
   */
  public function getModified() {
    return false;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.getsignature.php )
   *
   * @return array Array with the opened archive's signature in hash key and
   *               MD5, SHA-1, SHA-256, SHA-512, or OpenSSL in hash_type. This
   *               signature is a hash calculated on the entire phar's
   *               contents, and may be used to verify the integrity of the
   *               archive. A valid signature is absolutely required of all
   *               executable phar archives if the phar.require_hash INI
   *               variable is set to true.
   */
  public function getSignature() {
    return null;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.getstub.php )
   *
   * @return string a string containing the contents of the bootstrap loader
   *                (stub) of the current Phar archive.
   */
  public function getStub() {
    return $this->stub;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.getversion.php )
   *
   * @return string The opened archive's API version. This is not to be
   *                confused with the API version that the loaded phar
   *                extension will use to create new phars. Each Phar archive
   *                has the API version hard-coded into its manifest. See Phar
   *                file format documentation for more information.
   */
  public function getVersion() {
    return $this->apiVersion;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.hasmetadata.php )
   *
   * @return bool <b>TRUE</b> if meta-data has been set, and <b>FALSE</b> if
   *              not.
   */
  public function hasMetadata() {
    return $this->metadata !== null;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.isbuffering.php )
   *
   * @return bool <b>TRUE</b> if the write operations are being buffer,
   *              <b>FALSE</b> otherwise.
   */
  public function isBuffering() {
    return false;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.iscompressed.php )
   *
   *
   * @return mixed Phar::GZ, Phar::BZ2 or <b>FALSE</b>
   */
  public function isCompressed() {
    return $this->compressed;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.isfileformat.php )
   *
   *
   * @param int $format Either Phar::PHAR, Phar::TAR, or
   *                    Phar::ZIP to test for the format of the archive.
   *
   * @return bool <b>TRUE</b> if the phar archive matches the file format
   *              requested by the parameter
   */
  public function isFileFormat($fileformat) {
    return $fileformat === self::PHAR;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.isvalidpharfilename.php )
   *
   *
   * @param string $filename The name or full path to a phar archive not yet
   *                         created.
   * @param bool $executable This parameter determines whether the filename
   *                         should be treated as a phar executable archive, or
   *                         a data non-executable archive.
   *
   * @return bool <b>TRUE</b> if the filename is valid, <b>FALSE</b> if not.
   */
  public static function isValidPharFilename (
    $filename,
    $executable = true
  ) {
    $parts = explode('.', $filename);
    return $executable ? in_array('phar', $parts) : count($parts) > 1;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.iswritable.php )
   *
   * @return bool <b>TRUE</b> if the phar archive can be modified
   */
  public function isWritable() {
    return false;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.offsetexists.php )
   *
   * @param string $offset The filename (relative path) to look for in a Phar.
   *
   * @return bool <b>TRUE</b> if the file exists within the phar, or
   *              <b>FALSE</b> if not.
   */
  public function offsetExists($offset) {
    return isset($this->fileInfo[$offset]);
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.offsetget.php )
   *
   *
   * @param string $offset The filename (relative path) to look for in a Phar.
   *
   * @return int A <b>PharFileInfo</b> object is returned that can be used to
   *                       iterate over a file's contents or to retrieve
   *                       information about the current file.
   */
  public function offsetGet($offset) {
    if (!$this->offsetExists($offset)) {
      return null;
    }
    $fi = $this->fileInfo[$offset];
    return new PharFileInfo(
      $this->iteratorRoot.$offset,
      new __SystemLib\ArchiveEntryStat(
        $fi[3], // crc32
        $fi[0], // size
        $fi[2], // compressed size
        $fi[1]  // timestamp
      )
    );
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.offsetset.php )
   *
   *
   * @param string $offset The filename (relative path) to modify in a Phar.
   * @param string $value  Content of the file.
   *
   * @return void No return values.
   */
  public function offsetSet($offset, $value) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.offsetunset.php )
   *
   *
   * @param string $offset The filename (relative path) to modify in a Phar.
   *
   * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
   */
  public function offsetUnset($offset) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.setalias.php )
   *
   *
   * @param string $alias A shorthand string that this archive can be referred
   *                      to in phar stream wrapper access.
   *
   * @return bool
   */
  public function setAlias($alias) {
    $this->alias = $alias;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.setdefaultstub.php )
   *
   *
   * @param string $index    Relative path within the phar archive to run if
   *                         accessed on the command-line
   * @param string $webindex Relative path within the phar archive to run if
   *                         accessed through a web browser
   *
   * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
   */
  public function setDefaultStub($index, $webindex = null) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.setmetadata.php )
   *
   *
   * @param mixed $metadata Any PHP variable containing information to store
   *                        that describes the phar archive
   *
   * @return void No value is returned.
   */
  public function setMetadata($metadata) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.setsignaturealgorithm.php )
   *
   *
   * @param int $sigtype       One of Phar::MD5,
   *                           Phar::SHA1, Phar::SHA256,
   *                           Phar::SHA512, or Phar::OPENSSL
   * @param string $privatekey The contents of an OpenSSL private key, as
   *                           extracted from a certificate or OpenSSL key
   *                           file:
   *                           <code>
   *                           $private =
   *                           openssl_get_privatekey(file_get_contents('private.pem'));
   *                           $pkey = '';
   *                           openssl_pkey_export($private, $pkey);
   *                           $p->setSignatureAlgorithm(Phar::OPENSSL, $pkey);
   *                           </code>
   *                           See phar introduction for instructions on
   *                           naming and placement of the public key file.
   *
   * @return void No value is returned.
   */
  public function setSignatureAlgorithm($sigtype, $privatekey = null) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.setstub.php )
   *
   *
   * @param string $stub A string or an open stream handle to use as the
   *                     executable stub for this phar archive.
   * @param int $len
   *
   * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
   */
  public function setStub($stub, $len = -1) {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.startbuffering.php )
   *
   *
   * @return void No value is returned.
   */
  public function startBuffering() {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.stopbuffering.php )
   *
   *
   * @return void No value is returned.
   */
  public function stopBuffering() {
    throw new UnexpectedValueException('phar is read-only');
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.canwrite.php )
   *
   * @return bool <b>TRUE</b> if write access is enabled, <b>FALSE</b> if it is
   *              disabled.
   */
  public static function canWrite()  {
    return false;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.apiversion.php )
   *
   * @return string The API version string as in "1.0.0".
   */
  final public static function apiVersion() {
    return '1.0.0';
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.cancompress.php )
   *
   * @param int $type Either Phar::GZ or Phar::BZ2 can be
   *                  used to test whether compression is possible with a
   *                  specific compression algorithm (zlib or bzip2).
   *
   * @return bool <b>TRUE</b> if compression/decompression is available,
   *              <b>FALSE</b> if not.
   */
  final public static function canCompress($type = 0) {
    return false;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.getsupportedcompression.php
   * )
   *
   *
   * @return array an array containing any of Phar::GZ or Phar::BZ2, depending
   *               on the availability of the zlib extension or the bz2
   *               extension.
   */
  final public static function getSupportedCompression() {
    return array();
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.getsupportedsignatures.php
   * )
   *
   * @return array an array containing any of MD5, SHA-1, SHA-256, SHA-512, or
   *               OpenSSL.
   */
  final public static function getSupportedSignatures () {
    return array();
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.loadphar.php )
   *
   *
   * @param string $filename the full or relative path to the phar archive to
   *                         open
   * @param string $alias    The alias that may be used to refer to the phar
   *                         archive. Note that many phar archives specify an
   *                         explicit alias inside the phar archive, and a
   *                         <b>PharException</b> will be thrown if a new alias
   *                         is specified in this case.
   *
   * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
   */
  final public static function loadPhar($filename, $alias = null) {
    // We need this hack because the stream wrapper should work
    // even without the __HALT_COMPILER token
    self::$preventHaltTokenCheck = true;
    new self($filename, null, $alias);
    self::$preventHaltTokenCheck = false;
    return true;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.mapphar.php )
   *
   *
   * @param string $alias   The alias that can be used in phar:// URLs to
   *                        refer to this archive, rather than its full path.
   * @param int $dataoffset Unused variable, here for compatibility with PEAR's
   *                        PHP_Archive.
   *
   * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
   */
  public static function mapPhar($alias = null, $dataoffset = 0) {
    // We need this hack because extension check during mapping is not needed
    self::$preventExtCheck = true;
    new self(debug_backtrace()[0]['file'], null, $alias);
    self::$preventExtCheck = false;
    return true;
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.interceptfilefuncs.php )
   *
   *
   * @return void
   */
  public static function interceptFileFuncs() {
    // Not supported (yet) but most phars call it, so don't throw
  }

  /**
   * ( excerpt from http://php.net/manual/en/phar.running.php )
   *
   * Returns the full path to the running phar archive. This is intended
   * for use much like the __FILE__ magic constant, and only has effect
   * inside an executing phar archive.
   *
   * Inside the stub of an archive, function Phar::running() returns "".
   * Simply use __FILE__ to access the current running phar inside a stub
   *
   * @param bool $retphar If <b>FALSE</b>, the full path on disk to the phar
   *                      archive is returned. If <b>TRUE</b>, a full phar URL
   *                      is returned.
   *
   * @return string the filename if valid, empty string otherwise.
   */
  final public static function running(bool $retphar = true) {
    $filename = debug_backtrace()[0]['file'];
    $pharScheme = "phar://";
    $pharExt = ".phar";
    if(substr($filename, 0, strlen($pharScheme)) == $pharScheme) {
      $pharExtPos = strrpos($filename, $pharExt);
      if($pharExtPos) {
        $endPos = $pharExtPos + strlen($pharExt);
        if($retphar) {
          return substr($filename, 0, $endPos);
        }
        else {
          return substr($filename, strlen($pharScheme),
            $endPos - strlen($pharScheme));
        }
      }
    }
    return "";
  }

  final public static function webPhar(
      $alias,
      $index = "index.php",
      $f404 = null,
      $mimetypes = null,
      $rewrites = null) {
    // This is in the default stub, but lets ignore it for now
  }

  /**
   * @param string $fname
   * @param int $flags
   * @param string $alias
   *
   * @throws PharException
   */
  private function construct_phar ($fname, $flags, $alias) {
    fseek($this->fp, 0);
    $data = stream_get_contents($this->fp);

    $pos = strpos($data, self::HALT_TOKEN);
    if ($pos === false && !self::$preventHaltTokenCheck) {
      throw new PharException(self::HALT_TOKEN.' must be declared in a phar');
    }
    $this->stub = substr($data, 0, $pos);

    $pos += strlen(self::HALT_TOKEN);
    // *sigh*. We have to allow whitespace then ending the file
    // before we start the manifest
    while ($data[$pos] == ' ') {
      $pos += 1;
    }
    if ($data[$pos] == '?' && $data[$pos+1] == '>') {
      $pos += 2;
    }
    while ($data[$pos] == "\r") {
      $pos += 1;
    }
    while ($data[$pos] == "\n") {
      $pos += 1;
    }

    $this->contents = substr($data, $pos);
    $this->parsePhar($data, $pos);

    if ($alias) {
      self::$aliases[$alias] = $this;
    }
    // From the manifest
    if ($this->alias) {
      self::$aliases[$this->alias] = $this;
    }
    // We also do filename lookups
    self::$aliases[$fname] = $this;

    $this->iteratorRoot = 'phar://'.realpath($fname).'/';
  }

  /**
   * @param string $fname
   * @param int $flags
   * @param string $alias
   *
   * @throws PharException
   */
  private function construct_zip ($fname, $flags, $alias) {
    // TODO: ZIP support
  }

  /**
   * @param string $fname
   * @param int $flags
   * @param string $alias
   *
   * @throws PharException
   */
  private function construct_tar ($fname, $flags, $alias) {
    // TODO: Tar support
  }

  private static function bytesToInt($str, &$pos, $len) {
    if (strlen($str) < $pos + $len) {
      throw new PharException(
        "Corrupt phar, can't read $len bytes starting at offset $pos"
      );
    }
    $int = 0;
    for ($i = 0; $i < $len; ++$i) {
      $int |= ord($str[$pos++]) << (8*$i);
    }
    return $int;
  }

  private static function substr($str, &$pos, $len) {
    $ret = substr($str, $pos, $len);
    $pos += $len;
    return $ret;
  }

  private function parsePhar($data, &$pos) {
    $start = $pos;
    $len = self::bytesToInt($data, $pos, 4);
    $this->count = self::bytesToInt($data, $pos, 4);
    $this->apiVersion = self::bytesToInt($data, $pos, 2);
    $this->archiveFlags = self::bytesToInt($data, $pos, 4);
    $alias_len = self::bytesToInt($data, $pos, 4);
    $this->alias = self::substr($data, $pos, $alias_len);
    $metadata_len = self::bytesToInt($data, $pos, 4);
    $this->metadata = unserialize(
      self::substr($data, $pos, $metadata_len)
    );
    $this->parseFileInfo($data, $pos);
    if ($pos != $start + $len + 4) {
      throw new PharException(
        "Malformed manifest. Expected $len bytes, got $pos"
      );
    }
    foreach ($this->fileInfo as $key => $info) {
      $this->fileOffsets[$key] = array($pos - $start, $info[2]);
      $pos += $info[2];
    }

    // Try to see if there is a signature
    if ($this->archiveFlags & self::SIGNATURE) {
      if (strlen($data) < 8 || substr($data, -4) !== 'GBMB') {
        // Not even the GBMB and the flags?
        throw new PharException('phar has a broken signature');
      }

      $pos = strlen($data) - 8;
      $this->signatureFlags = self::bytesToInt($data, $pos, 4);
      switch ($this->signatureFlags) {
        case self::MD5:
          $digestSize = 16;
          $digestName = 'md5';
          break;
        case self::SHA1:
          $digestSize = 20;
          $digestName = 'sha1';
          break;
        case self::SHA256:
          $digestSize = 32;
          $digestName = 'sha256';
          break;
        case self::SHA512:
          $digestSize = 64;
          $digestName = 'sha512';
          break;
        default:
          throw new PharException('phar has a broken or unsupported signature');
      }

      if (strlen($data) < 8 + $digestSize) {
        throw new PharException('phar has a broken signature');
      }

      $pos -= 4;
      $signatureStart = $pos - $digestSize;
      $this->signature = substr($data, $signatureStart, $digestSize);
      $actualHash = self::verifyHash($data, $digestName, $signatureStart);

      if ($actualHash !== $this->signature) {
        throw new PharException('phar has a broken signature');
      }
    }
  }

  private function parseFileInfo($str, &$pos) {
    for ($i = 0; $i < $this->count; $i++) {
      $filename_len = self::bytesToInt($str, $pos, 4);
      $filename = self::substr($str, $pos, $filename_len);
      $filesize = self::bytesToInt($str, $pos, 4);
      $timestamp = self::bytesToInt($str, $pos, 4);
      $compressed_filesize = self::bytesToInt($str, $pos, 4);
      $crc32 = self::bytesToInt($str, $pos, 4);
      $flags = self::bytesToInt($str, $pos, 4);
      $metadata_len = self::bytesToInt($str, $pos, 4);
      $metadata = self::bytesToInt($str, $pos, $metadata_len);
      $this->fileInfo[$filename] = array(
        $filesize, $timestamp, $compressed_filesize, $crc32, $flags, $metadata
      );
    }
  }

  private static function verifyHash($str, $algorithm, $signatureOffset) {
    return hash($algorithm, substr($str, 0, $signatureOffset), true);
  }

  /**
   * A poor man's FileUtil::canonicalize in PHP
   */
  private static function resolveDotDots($pieces) {
    $starts_with_slash = false;
    if (count($pieces) > 0 && !strlen($pieces[0])) {
      $starts_with_slash = true;
    }

    foreach ($pieces as $i => $piece) {
      if ($piece == '.') {
        $piece[$i] = '';
      } else if ($piece == '..' && $i > 0) {
        $pieces[$i] = '';
        while ($i > 0 && !$pieces[$i-1]) {
          $i--;
        }
        $pieces[$i-1] = '';
      }
    }
    // strlen is used to remove empty strings, but keep values of 0 (zero)
    return ($starts_with_slash ? '/' : '') .
           implode('/', array_filter($pieces, 'strlen'));
  }

  /**
   * BELOW THIS ISN'T PART OF THE ZEND API. THEY ARE FOR THE STREAM WRAPPER.
   */

  /**
   * For the stream wrapper to stat a file. Same response format as stat().
   * Called from C++.
   */
  private static function stat($full_filename) {
    list($phar, $filename) = self::getPharAndFile($full_filename);
    if (!isset($phar->fileInfo[$filename])) {
      $dir = self::opendir($full_filename);
      if (!$dir) {
        return false;
      }

      return array(
        'size' => 0,
        'atime' => 0,
        'mtime' => 0,
        'ctime' => 0,
        'mode' => POSIX_S_IFDIR,
      );
    }

    $info = $phar->fileInfo[$filename];
    return array(
      'size' => $info[0],
      'atime' => $info[1],
      'mtime' => $info[1],
      'ctime' => $info[1],
      'mode' => POSIX_S_IFREG,
    );
  }

  /**
   * Simulates opendir() and readdir() and rewinddir() using an array.
   * Returns any files that start with $prefix.
   * Called from C++.
   */
  private static function opendir($full_prefix) {
    list($phar, $prefix) = self::getPharAndFile($full_prefix);
    $prefix = rtrim($prefix, '/');

    $ret = array();
    foreach ($phar->fileInfo as $filename => $_) {
      if (!$prefix) {
        if (strpos($filename, '/') === false) {
          $ret[$filename] = true;
        }
      } else {
        if (strpos($filename, $prefix) === 0) {
          $entry = substr($filename, strlen($prefix) + 1);
          if (strlen($entry) > 0) {
            if ($filename[strlen($prefix)] != '/') {
              continue;
            }
            $next_slash = strpos($entry, '/');
            if ($next_slash !== false) {
              $entry = substr($entry, 0, $next_slash);
            }
            $ret[$entry] = true;
          }
        }
      }
    }
    return array_keys($ret);
  }

  /**
   * Used by the stream wrapper to open phar:// files.
   * Called from C++.
   */
  private static function openPhar($full_filename) {
    list($phar, $filename) = self::getPharAndFile($full_filename);
    return $phar->getFileData($filename);
  }

  private function getFileData($filename) {
    if (!isset($this->fileOffsets[$filename])) {
      throw new PharException("No $filename in phar");
    }
    $offsets = $this->fileOffsets[$filename];
    return substr($this->contents, $offsets[0], $offsets[1]);
  }

  /**
   * Checks through a phar://path/to/file.phar/other/path.php and returns
   *
   *   array([Phar object for path/to/file.phar], 'other/path.php')
   *
   * or if the first piece is a valid alias, then returns
   *
   *   array([Phar object for alias], 'rest/of/path.php')
   */
  private static function getPharAndFile($filename_or_alias) {
    if (strncmp($filename_or_alias, 'phar://', 7)) {
      throw new PharException("Not a phar: $filename_or_alias");
    }

    $pieces = explode('/', substr($filename_or_alias, 7));

    if (count($pieces) > 0 && isset(self::$aliases[$pieces[0]])) {
      $alias = array_shift($pieces);
      return array(
        self::$aliases[$alias],
        self::resolveDotDots($pieces)
      );
    }

    $filename = '';
    while ($pieces) {
      $filename .= '/'.array_shift($pieces);
      if (is_file($filename)) {

        if (!isset(self::$aliases[$filename])) {
          self::loadPhar($filename);
        }

        return array(
          self::$aliases[$filename],
          self::resolveDotDots($pieces)
        );
      }
    }

    throw new PharException("Not a phar: $filename_or_alias");
  }

  protected function getIteratorFromList(string $root, array $list) {
    $tree = array();
    foreach ($list as $filename => $info) {
      $dir = dirname($filename);
      $current = &$tree;
      if ($dir !== '') {
        $path = $root;
        foreach (explode('/', $dir) as $part) {
          $path .= $part.'/';
          if (!isset($current[$path])) {
            $current[$path] = array();
          }
          $current = &$current[$path];
        }
      }
      $current[$root.$filename] = $info;
    }
    return new RecursiveArrayIterator(
      $tree,
      RecursiveArrayIterator::CHILD_ARRAYS_ONLY
    );
  }

  protected function getIterator() {
    if ($this->iterator !== null) {
      return $this->iterator;
    }
    $filenames = array_keys($this->fileInfo);
    $info = array();
    foreach ($filenames as $filename) {
      $info[$filename] = $this->offsetGet($filename);
    }
    $this->iterator = $this->getIteratorFromList($this->iteratorRoot, $info);
    return $this->iterator;
  }

  public function key() {
    return $this->getIterator()->key();
  }

  public function current() {
    return $this->getIterator()->current();
  }

  public function next() {
    $this->getIterator()->next();
  }

  public function rewind() {
    $this->getIterator()->rewind();
  }

  public function valid() {
    return $this->getIterator()->valid();
  }

  public function hasChildren() {
    return $this->getIterator()->hasChildren();
  }

  public function getChildren() {
    return $this->getIterator()->getChildren();
  }

  private function __destruct() {
    if ($this->fp !== null) {
      fclose($this->fp);
    }
  }
}

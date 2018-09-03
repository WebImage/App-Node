<?php

namespace WebImage\Node\Service;

use Exception;

// https://svn.alfresco.com/repos/alfresco-open-mirror/alfresco/HEAD/root/projects/repository/source/java/org/alfresco/service/namespace/QName.java
class _QName {
	const NAMESPACE_PREFIX = ':';
	const NAMESPACE_BEGIN = '{';
	const NAMESPACE_END = '}';
	const MAX_LENGTH = 100;

	/**
	 * @var string
	 */
	private $namespace;
	/**
	 * @var string
	 */
	private $localName;
	/**
	 * @var string
	 */
	private $prefix;

	public function __construct($namespace, $localName, $prefix)
	{
		$this->namespace = null === $namespace ? 'DEFAULT_NAMESPACE' : $namespace;
		$this->localName = $localName;
		$this->prefix = $prefix;
	}

	/**
	 * Creates a QName object
	 * Possible values are:
	 * 	- createQName(string $qname)
	 *	- createQName(string $namespace, string $local_name)
	 * 	- createQName(string $qname, NamespacePrefixResolver $prefixResolver)
	 *	- createQName(string $prefix, string $local_name, $prefixResolver)
	 *
	 * @throws Exception When an invalid createQName configuration is called
	 *
	 * @return QName
	 */
	public static function createQName($param1, $param2 = null, $param3 = null)
	{
		if (is_string($param1) && null === $param2 && null === $param3) { // createQName($string_qname)
			return self::createQNameWithQNameAsString($param1);
		} else if (is_string($param1) && is_string($param2) && null === $param3) { // createQName(string $namespace, string $local_name)
			return self::createQNameWithNamespaceAndLocalName($param1, $param2);
		} else if (is_string($param1) && is_object($param2) && null === $param3) { // createQName (string $qname, NamespacePrefixResolver $prefixResolver)
			return self::createQNameWithStringQNameAndResolver($param1, $param2);
		} else if (is_string($param1) && is_string($param2) && is_object($param3)) { // createQName(string $prefix, string $local_name, NamespacePrefixResolver $prefixResolver)
			return self::createQNameWithPrefixAndResolver($param1, $param2, $param3);
		} else {
			throw new Exception('Invalid createQName construction.');
		}
	}

	/**
	 * Creates a QName using the passed string representation
	 *
	 * @param string $qname
	 * @return QName
	 */
	private static function createQNameWithQNameAsString($qname)
	{
		$namespace = null;
		$local_name = null;

		// Parse namespace
		$namespace_begin = strpos($qname, QName::NAMESPACE_BEGIN);
		$namespace_end = -1;

		if ($namespace_begin !== false) {

			if ($namespace_begin != 0) throw new Exception("QName '" . $qname . "' must start with a namespace.");
			$namespace_end = strpos($qname, QName::NAMESPACE_END, $namespace_begin + 1);

			if ($namespace_end === false) {
				throw new Exception("QName '" . $qname . "' is missing the closing namespace " . QName::NAMESPACE_END . " token.");
			}
			$namespace = substr($qname, $namespace_begin + 1, $namespace_end - 1);

		}

		// Parse name
		$local_name = substr($qname, $namespace_end + 1);
		if (strlen($local_name) == 0) throw new Exception("QName '" . $qname . "' must consist of a local name.");

		return new QName($namespace, $local_name, null);
	}

	/**
	 * Create a QName with a namespace and local name
	 *
	 * @param string $namespace
	 * @param string $localName
	 *
	 * @return QName
	 */
	private static function createQNameWithNamespaceAndLocalName($namespace, $localName)
	{
		return new QName($namespace, $localName, null);
	}

	/**
	 * Create a QName with a string and prefix resolver
	 *
	 * @param string $qname
	 * @param NamespacePrefixResolver $prefixResolver
	 *
	 * @return QName
	 */
	private static function createQNameWithStringQNameAndResolver($qname, $prefixResolver)
	{
		$colon_index = strpos($qname, QName::NAMESPACE_PREFIX);
		$prefix = ($colon_index === false) ? 'DEFAULT_NAMESPACE' : substr($qname, 0, $colon_index);
		$local_name = ($colon_index === false) ? $qname : substr($qname, $colon_index + 1);

		return QName::createQName($prefix, $local_name, $prefixResolver);
	}

	/**
	 * Create a QName with a prefix, local name, and prefix resolver
	 *
	 * @param string $prefix
	 * @param string $localName
	 * @param NamespacePrefixResolver $prefixResolver
	 *
	 * @throws Exception When namespace prefix is not found
	 *
	 * @return QName
	 */
	private static function createQNameWithPrefixAndResolver($prefix, $localName, $prefixResolver)
	{
		if (strlen($localName) == 0) throw new Exception('A QName must consit of a local name.');

		if (!$namespace = $prefixResolver->getNamespace($prefix)) throw new Exception('Namespace prefix ' . $prefix . ' is not mapped to a namespace.');

		return new QName($namespace, $localName, $prefix);
	}

	/**
	 * Get the local name
	 *
	 * @return string
	 */
	public function getLocalName()
	{
		return $this->localName;
	}

	/**
	 * Get the namespace
	 *
	 * @return string
	 */
	public function getNamespace()
	{
		return $this->namespace;
	}

	/**
	 * Get the prefix
	 *
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

#	public function getPrefixedQName(NamespacePrefixResolver $resolver) {
#		$prefix = $resolver->getPrefixes($this->getNamespace());
#		return new Qname($this->getNamespace(), $this->getLocalName(), $prefix);
#	}
	/**
	 * public function equals() {}
	 * public function isMatch($qname) {}
	 */
	public function toString()
	{
		return QName::NAMESPACE_BEGIN . $this->getNamespace() . QName::NAMESPACE_END . $this->getLocalName();
	}

	public function toPrefixString()
	{
		return null === $this->prefix ? $this->localName : $this->prefix . QName::NAMESPACE_PREFIX . $this->getLocalName();
	}
}
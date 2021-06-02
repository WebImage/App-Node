<?php

namespace WebImage\Node\DataTypes\ValueMapper;

class DateTimeMapper implements ValueMapperInterface
{
	public function forStorage($dateTime)
	{
		if (null === $dateTime) return null;

		return $this->convertDateTimeToString($dateTime);
	}

	private function convertDateTimeToString(\DateTime $dateTime)
	{
		return $dateTime->format('Y-m-d H:i:s');
	}

	public function forProperty($value)
	{
		if (null === $value) return null;

		return \DateTime::createFromFormat('Y-m-d H:i:s', $value);
	}
}
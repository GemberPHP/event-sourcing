<?php

declare(strict_types=1);

namespace GemberReflectorTest;

/**
 * In a namespace of one segment, which is tokenized differently from one of more segments.
 * Not autoloadable: require this file before reflecting it.
 */
final class SingleSegmentNamespaceClass {}
